<?php

namespace Tests\Feature\Administracao;

use App\Models\AtribuicaoPerfil;
use App\Models\OrganizacaoSaude;
use App\Models\Perfil;
use App\Models\RegistroAuditoria;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ModulosAdministrativosTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_sem_permissao_nao_acessa_modulos_administrativos(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

        foreach (['/organizacoes', '/unidades', '/usuarios', '/perfis', '/auditoria'] as $url) {
            $this->actingAs($usuario)->get($url)->assertForbidden();
        }
    }

    public function test_superadministrador_cadastra_organizacao_e_unidade_com_auditoria(): void
    {
        $usuario = $this->criarUsuarioComPerfil();

        $this->actingAs($usuario)->post('/organizacoes', [
            'nome' => 'Secretaria Municipal de Saúde',
            'sigla' => 'sms',
            'cnpj' => '12.345.678/0001-99',
            'ativo' => true,
        ])->assertRedirect();

        $organizacao = OrganizacaoSaude::query()->firstOrFail();
        $this->assertSame('12345678000199', $organizacao->cnpj);

        $this->actingAs($usuario)->post('/unidades', [
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Unidade Central',
            'cnes' => '1234567',
            'tipo' => 'ubs',
            'ativo' => true,
        ])->assertRedirect();

        $unidade = UnidadeSaude::query()->firstOrFail();
        $this->assertDatabaseHas('registros_auditoria', ['evento' => 'organizacao.criada', 'entidade_id' => $organizacao->id]);
        $this->assertDatabaseHas('registros_auditoria', ['evento' => 'unidade.criada', 'entidade_id' => $unidade->id]);
    }

    public function test_unidade_ativa_nao_pode_pertencer_a_organizacao_inativa(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Organização inativa', 'ativo' => false]);

        $this->actingAs($usuario)->post('/unidades', [
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Unidade',
            'tipo' => 'ubs',
            'ativo' => true,
        ])->assertSessionHasErrors('organizacao_saude_id');
    }

    public function test_administrador_de_organizacao_visualiza_apenas_seu_escopo(): void
    {
        $organizacaoA = OrganizacaoSaude::query()->create(['nome' => 'Organização A', 'ativo' => true]);
        $organizacaoB = OrganizacaoSaude::query()->create(['nome' => 'Organização B', 'ativo' => true]);
        $unidadeA = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacaoA->id, 'nome' => 'Unidade A', 'tipo' => 'ubs', 'ativo' => true]);
        UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacaoB->id, 'nome' => 'Unidade B', 'tipo' => 'ubs', 'ativo' => true]);
        $administrador = $this->criarUsuarioComPerfil('administrador_organizacao', 'organizacao', organizacaoId: $organizacaoA->id);

        $this->actingAs($administrador)->get('/organizacoes')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('organizacoes.data', 1)
                ->where('organizacoes.data.0.nome', 'Organização A'));

        $this->actingAs($administrador)->get('/unidades')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('unidades.data', 1)
                ->where('unidades.data.0.id', $unidadeA->id));

        $this->actingAs($administrador)->get(route('unidades.edit', UnidadeSaude::query()->where('nome', 'Unidade B')->first()))
            ->assertForbidden();
    }

    public function test_cria_usuario_com_atribuicao_e_impede_elevacao_indevida(): void
    {
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Organização', 'ativo' => true]);
        $administrador = $this->criarUsuarioComPerfil('administrador_organizacao', 'organizacao', organizacaoId: $organizacao->id);
        $perfilGestor = Perfil::query()->where('chave', 'gestor_unidade')->firstOrFail();
        $perfilSuper = Perfil::query()->where('chave', 'superadministrador_sistema')->firstOrFail();
        $unidade = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade', 'tipo' => 'ubs', 'ativo' => true]);

        $this->actingAs($administrador)->post('/usuarios', [
            'name' => 'Gestor',
            'email' => 'gestor@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
            'ativo' => true,
            'perfil_id' => $perfilGestor->id,
            'tipo_escopo' => 'unidade',
            'unidade_saude_id' => $unidade->id,
        ])->assertRedirect();

        $gestor = User::query()->where('email', 'gestor@example.com')->firstOrFail();
        $this->assertDatabaseHas('atribuicoes_perfil', ['user_id' => $gestor->id, 'perfil_id' => $perfilGestor->id, 'unidade_saude_id' => $unidade->id, 'ativo' => true]);

        $this->actingAs($administrador)->post("/usuarios/{$gestor->id}/atribuicoes", [
            'perfil_id' => $perfilSuper->id,
            'tipo_escopo' => 'sistema',
        ])->assertSessionHasErrors('perfil_id');
    }

    public function test_nao_revoga_o_proprio_superadministrador(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        $atribuicao = $usuario->atribuicoesPerfil()->firstOrFail();

        $this->actingAs($usuario)->delete("/usuarios/{$usuario->id}/atribuicoes/{$atribuicao->id}")
            ->assertSessionHasErrors('atribuicao');

        $this->assertTrue($atribuicao->fresh()->ativo);
    }

    public function test_auditoria_respeita_escopo_da_organizacao(): void
    {
        $organizacaoA = OrganizacaoSaude::query()->create(['nome' => 'Organização A', 'ativo' => true]);
        $organizacaoB = OrganizacaoSaude::query()->create(['nome' => 'Organização B', 'ativo' => true]);
        $administrador = $this->criarUsuarioComPerfil('administrador_organizacao', 'organizacao', organizacaoId: $organizacaoA->id);
        RegistroAuditoria::query()->create(['evento' => 'evento.a', 'organizacao_saude_id' => $organizacaoA->id, 'ocorrido_em' => now()]);
        RegistroAuditoria::query()->create(['evento' => 'evento.b', 'organizacao_saude_id' => $organizacaoB->id, 'ocorrido_em' => now()]);

        $this->actingAs($administrador)->get('/auditoria')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('registros.data', 1)
                ->where('registros.data.0.evento', 'evento.a'));
    }

    public function test_inertia_compartilha_capacidades_do_usuario(): void
    {
        $usuario = $this->criarUsuarioComPerfil();

        $this->actingAs($usuario)->get('/')
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->where('auth.usuario.id', $usuario->id)
                ->where('auth.capacidades.profissionais.visualizar', true)
                ->where('auth.capacidades.auditoria.visualizar', true));
    }
}
