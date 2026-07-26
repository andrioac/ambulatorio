<?php

namespace Tests\Feature\Profissionais;

use App\Models\OrganizacaoSaude;
use App\Models\Profissional;
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

    public function test_cadastra_profissional_pela_interface(): void
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
    }

    public function test_adiciona_e_desativa_vinculo_pela_interface(): void
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

        $this->actingAs($usuario)->delete("/profissionais/{$profissional->id}/vinculos/{$vinculo->id}")
            ->assertRedirect();

        $this->assertFalse($vinculo->fresh()->ativo);
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
