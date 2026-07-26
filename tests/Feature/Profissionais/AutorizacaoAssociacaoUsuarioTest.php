<?php

namespace Tests\Feature\Profissionais;

use App\Models\OrganizacaoSaude;
use App\Models\UnidadeSaude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorizacaoAssociacaoUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_gestor_nao_associa_profissional_a_usuario_de_outra_unidade(): void
    {
        $organizacao = OrganizacaoSaude::query()->create(['nome' => 'Secretaria', 'ativo' => true]);
        $unidadeA = UnidadeSaude::query()->create([
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Unidade A',
            'tipo' => 'ubs',
            'ativo' => true,
        ]);
        $unidadeB = UnidadeSaude::query()->create([
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Unidade B',
            'tipo' => 'ubs',
            'ativo' => true,
        ]);
        $gestorA = $this->criarUsuarioComPerfil(
            'gestor_unidade',
            'unidade',
            unidadeId: $unidadeA->id,
            atributos: ['email' => 'gestor-a@example.com'],
        );
        $usuarioB = $this->criarUsuarioComPerfil(
            'gestor_unidade',
            'unidade',
            unidadeId: $unidadeB->id,
            atributos: ['email' => 'gestor-b@example.com'],
        );

        $this->actingAs($gestorA)->post('/profissionais', [
            'user_id' => $usuarioB->id,
            'nome' => 'Profissional fora do escopo',
            'categoria' => 'medicina',
            'ativo' => true,
            'unidade_saude_id_inicial' => $unidadeA->id,
        ])->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('profissionais', ['user_id' => $usuarioB->id]);
    }
}
