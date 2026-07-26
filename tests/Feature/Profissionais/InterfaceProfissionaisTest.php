<?php

namespace Tests\Feature\Profissionais;

use App\Models\OrganizacaoSaude;
use App\Models\Profissional;
use App\Models\RegistroAuditoria;
use App\Models\UnidadeSaude;
use App\Models\User;
use App\Models\VinculoProfissionalUnidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InterfaceProfissionaisTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_profissionais(): void
    {
        $this->get('/profissionais')->assertRedirect('/entrar');
    }

    public function test_usuario_sem_permissao_recebe_403(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($usuario)->get('/profissionais')->assertForbidden();
    }

    public function test_superadministrador_visualiza_listagem(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        Profissional::query()->create(['nome' => 'Maria Médica', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->get('/profissionais')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->component('Profissionais/Index')
                ->has('profissionais.data', 1)
                ->where('profissionais.data.0.nome', 'Maria Médica'));
    }

    public function test_gestor_visualiza_apenas_profissionais_da_sua_unidade(): void
    {
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria', 'ativo' => true]);
        $unidadeA = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade A', 'tipo' => 'ubs', 'ativo' => true]);
        $unidadeB = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade B', 'tipo' => 'ubs', 'ativo' => true]);
        $gestor = $this->criarUsuarioComPerfil('gestor_unidade', 'unidade', unidadeId: $unidadeA->id);
        $profissionalA = Profissional::query()->create(['nome' => 'Profissional A', 'categoria' => 'medicina', 'ativo' => true]);
        $profissionalB = Profissional::query()->create(['nome' => 'Profissional B', 'categoria' => 'medicina', 'ativo' => true]);
        VinculoProfissionalUnidade::query()->create(['profissional_id' => $profissionalA->id, 'unidade_saude_id' => $unidadeA->id, 'ativo' => true]);
        VinculoProfissionalUnidade::query()->create(['profissional_id' => $profissionalB->id, 'unidade_saude_id' => $unidadeB->id, 'ativo' => true]);

        $this->actingAs($gestor)->get('/profissionais')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('profissionais.data', 1)
                ->where('profissionais.data.0.nome', 'Profissional A'));

        $this->actingAs($gestor)->get(route('profissionais.edit', $profissionalB))->assertForbidden();
    }

    public function test_cadastra_profissional_pela_interface_e_registra_auditoria(): void
    {
        $usuario = $this->criarUsuarioComPerfil();

        $resposta = $this->actingAs($usuario)->post('/profissionais', [
            'nome' => '  João Enfermeiro  ',
            'categoria' => ' Enfermagem ',
            'cpf' => '123.456.789-01',
            'ativo' => true,
        ]);

        $profissional = Profissional::query()->firstOrFail();
        $resposta->assertRedirect(route('profissionais.edit', $profissional));
        $this->assertSame('João Enfermeiro', $profissional->nome);
        $this->assertSame('12345678901', $profissional->cpf);
        $this->assertDatabaseHas('registros_auditoria', [
            'ator_user_id' => $usuario->id,
            'evento' => 'profissional.criado',
            'entidade_tipo' => Profissional::class,
            'entidade_id' => $profissional->id,
        ]);
    }

    public function test_gestor_precisa_informar_unidade_inicial_ao_cadastrar(): void
    {
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria', 'ativo' => true]);
        $unidade = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade', 'tipo' => 'ubs', 'ativo' => true]);
        $gestor = $this->criarUsuarioComPerfil('gestor_unidade', 'unidade', unidadeId: $unidade->id);

        $this->actingAs($gestor)->post('/profissionais', [
            'nome' => 'Profissional', 'categoria' => 'medicina', 'ativo' => true,
        ])->assertSessionHasErrors('unidade_saude_id_inicial');

        $this->actingAs($gestor)->post('/profissionais', [
            'nome' => 'Profissional', 'categoria' => 'medicina', 'ativo' => true, 'unidade_saude_id_inicial' => $unidade->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('vinculos_profissionais_unidades', ['unidade_saude_id' => $unidade->id, 'ativo' => true]);
    }

    public function test_atualiza_profissional_e_registra_estado_anterior_e_posterior(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        $profissional = Profissional::query()->create(['nome' => 'Nome antigo', 'categoria' => 'enfermagem', 'ativo' => true]);

        $this->actingAs($usuario)->put("/profissionais/{$profissional->id}", [
            'nome' => 'Nome atualizado', 'categoria' => 'medicina', 'ativo' => false,
        ])->assertRedirect();

        $registro = RegistroAuditoria::query()->where('evento', 'profissional.atualizado')->firstOrFail();
        $this->assertSame($usuario->id, $registro->ator_user_id);
        $this->assertSame('Nome antigo', $registro->dados_anteriores['nome']);
        $this->assertSame('Nome atualizado', $registro->dados_posteriores['nome']);
        $this->assertTrue($registro->dados_anteriores['ativo']);
        $this->assertFalse($registro->dados_posteriores['ativo']);
    }

    public function test_adiciona_atualiza_desativa_e_reativa_vinculo_com_auditoria(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria de Saúde', 'sigla' => 'SMS', 'ativo' => true]);
        $unidade = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Ambulatório Central', 'tipo' => 'ambulatorio', 'ativo' => true]);
        $profissional = Profissional::query()->create(['nome' => 'Ana Médica', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->post("/profissionais/{$profissional->id}/vinculos", [
            'unidade_saude_id' => $unidade->id, 'vigente_de' => '2026-07-01', 'vigente_ate' => '2026-12-31',
        ])->assertRedirect();

        $vinculo = $profissional->vinculosUnidades()->firstOrFail();
        $this->actingAs($usuario)->put("/profissionais/{$profissional->id}/vinculos/{$vinculo->id}", [
            'unidade_saude_id' => $unidade->id, 'vigente_de' => '2026-08-01', 'vigente_ate' => '2027-01-31',
        ])->assertRedirect();
        $this->assertSame('2026-08-01', $vinculo->fresh()->vigente_de->toDateString());

        $this->actingAs($usuario)->delete("/profissionais/{$profissional->id}/vinculos/{$vinculo->id}")->assertRedirect();
        $this->assertFalse($vinculo->fresh()->ativo);

        $this->actingAs($usuario)->patch("/profissionais/{$profissional->id}/vinculos/{$vinculo->id}/reativar")->assertRedirect();
        $this->assertTrue($vinculo->fresh()->ativo);

        foreach (['profissional.vinculo_criado', 'profissional.vinculo_atualizado', 'profissional.vinculo_desativado', 'profissional.vinculo_reativado'] as $evento) {
            $this->assertDatabaseHas('registros_auditoria', ['ator_user_id' => $usuario->id, 'evento' => $evento, 'entidade_id' => $vinculo->id]);
        }
    }

    public function test_nao_vincula_profissional_a_unidade_inativa(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria', 'ativo' => true]);
        $unidade = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade', 'tipo' => 'ubs', 'ativo' => false]);
        $profissional = Profissional::query()->create(['nome' => 'Profissional', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->post("/profissionais/{$profissional->id}/vinculos", ['unidade_saude_id' => $unidade->id])
            ->assertSessionHasErrors('unidade_saude_id');
    }

    public function test_valida_intervalo_do_vinculo(): void
    {
        $usuario = $this->criarUsuarioComPerfil();
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria', 'sigla' => 'SMS', 'ativo' => true]);
        $unidade = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade', 'tipo' => 'ambulatorio', 'ativo' => true]);
        $profissional = Profissional::query()->create(['nome' => 'Profissional', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->from(route('profissionais.edit', $profissional))->post("/profissionais/{$profissional->id}/vinculos", [
            'unidade_saude_id' => $unidade->id, 'vigente_de' => '2026-12-31', 'vigente_ate' => '2026-07-01',
        ])->assertSessionHasErrors('vigente_ate');
    }
}
