<?php

namespace Tests\Feature\Autorizacao;

use App\Models\OrganizacaoSaude;
use App\Models\UnidadeSaude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EscoposInativosTest extends TestCase
{
    use RefreshDatabase;

    public function test_unidade_inativa_nao_concede_permissoes_nem_capacidades(): void
    {
        $organizacao = OrganizacaoSaude::query()->create([
            'nome' => 'Organização',
            'ativo' => true,
        ]);
        $unidade = UnidadeSaude::query()->create([
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Unidade inativa',
            'tipo' => 'ubs',
            'ativo' => false,
        ]);
        $gestor = $this->criarUsuarioComPerfil(
            'gestor_unidade',
            'unidade',
            unidadeId: $unidade->id,
        );

        $this->actingAs($gestor)->get('/profissionais')->assertForbidden();

        $this->actingAs($gestor)->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->where('auth.capacidades', function (Collection $capacidades): bool {
                    return $capacidades->get('profissionais.visualizar') === false
                        && $capacidades->get('auditoria.visualizar') === false;
                }));
    }

    public function test_organizacao_inativa_invalida_permissao_da_unidade_filha(): void
    {
        $organizacao = OrganizacaoSaude::query()->create([
            'nome' => 'Organização inativa',
            'ativo' => false,
        ]);
        $unidade = UnidadeSaude::query()->create([
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Unidade',
            'tipo' => 'ubs',
            'ativo' => true,
        ]);
        $gestor = $this->criarUsuarioComPerfil(
            'gestor_unidade',
            'unidade',
            unidadeId: $unidade->id,
        );

        $this->actingAs($gestor)->get('/profissionais')->assertForbidden();
    }
}
