<?php

namespace Tests\Feature\Profissionais;

use App\Models\OrganizacaoSaude;
use App\Models\Profissional;
use App\Models\RegistroAuditoria;
use App\Models\UnidadeSaude;
use App\Models\User;
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

    public function test_usuario_autenticado_visualiza_listagem(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);
        Profissional::query()->create(['nome' => 'Maria Médica', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->get('/profissionais')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->component('Profissionais/Index')
                ->has('profissionais.data', 1)
                ->where('profissionais.data.0.nome', 'Maria Médica'));
    }

    public function test_cadastra_profissional_pela_interface_e_registra_auditoria(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

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

    public function test_atualiza_profissional_e_registra_estado_anterior_e_posterior(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);
        $profissional = Profissional::query()->create([
            'nome' => 'Nome antigo',
            'categoria' => 'enfermagem',
            'ativo' => true,
        ]);

        $this->actingAs($usuario)->put("/profissionais/{$profissional->id}", [
            'nome' => 'Nome atualizado',
            'categoria' => 'medicina',
            'ativo' => false,
        ])->assertRedirect();

        $registro = RegistroAuditoria::query()->where('evento', 'profissional.atualizado')->firstOrFail();

        $this->assertSame($usuario->id, $registro->ator_user_id);
        $this->assertSame('Nome antigo', $registro->dados_anteriores['nome']);
        $this->assertSame('Nome atualizado', $registro->dados_posteriores['nome']);
        $this->assertTrue($registro->dados_anteriores['ativo']);
        $this->assertFalse($registro->dados_posteriores['ativo']);
    }

    public function test_adiciona_e_desativa_vinculo_pela_interface_com_auditoria(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria de Saúde', 'sigla' => 'SMS', 'ativo' => true]);
        $unidade = UnidadeSaude::query()->create([
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Ambulatório Central',
            'tipo' => 'ambulatorio',
            'ativo' => true,
        ]);
        $profissional = Profissional::query()->create(['nome' => 'Ana Médica', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->post("/profissionais/{$profissional->id}/vinculos", [
            'unidade_saude_id' => $unidade->id,
            'vigente_de' => '2026-07-01',
            'vigente_ate' => '2026-12-31',
        ])->assertRedirect();

        $vinculo = $profissional->vinculosUnidades()->firstOrFail();
        $this->assertTrue($vinculo->ativo);
        $this->assertDatabaseHas('registros_auditoria', [
            'ator_user_id' => $usuario->id,
            'evento' => 'profissional.vinculo_criado',
            'entidade_id' => $vinculo->id,
            'organizacao_saude_id' => $organizacao->id,
            'unidade_saude_id' => $unidade->id,
        ]);

        $this->actingAs($usuario)->delete("/profissionais/{$profissional->id}/vinculos/{$vinculo->id}")
            ->assertRedirect();

        $this->assertFalse($vinculo->fresh()->ativo);
        $this->assertDatabaseHas('registros_auditoria', [
            'ator_user_id' => $usuario->id,
            'evento' => 'profissional.vinculo_desativado',
            'entidade_id' => $vinculo->id,
            'unidade_saude_id' => $unidade->id,
        ]);
    }

    public function test_valida_intervalo_do_vinculo(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria', 'sigla' => 'SMS', 'ativo' => true]);
        $unidade = UnidadeSaude::query()->create(['organizacao_saude_id' => $organizacao->id, 'nome' => 'Unidade', 'tipo' => 'ambulatorio', 'ativo' => true]);
        $profissional = Profissional::query()->create(['nome' => 'Profissional', 'categoria' => 'medicina', 'ativo' => true]);

        $this->actingAs($usuario)->from(route('profissionais.edit', $profissional))->post("/profissionais/{$profissional->id}/vinculos", [
            'unidade_saude_id' => $unidade->id,
            'vigente_de' => '2026-12-31',
            'vigente_ate' => '2026-07-01',
        ])->assertSessionHasErrors('vigente_ate');
    }
}
