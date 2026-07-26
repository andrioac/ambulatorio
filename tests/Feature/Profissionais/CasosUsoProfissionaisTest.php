<?php

namespace Tests\Feature\Profissionais;

use App\Aplicacao\Profissionais\SalvarProfissional;
use App\Aplicacao\Profissionais\SalvarVinculoProfissionalUnidade;
use App\Models\OrganizacaoSaude;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Profissional;
use App\Models\UnidadeSaude;
use App\Models\User;
use Database\Seeders\CatalogoAutorizacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasosUsoProfissionaisTest extends TestCase
{
    use RefreshDatabase;

    public function test_salva_profissional_normalizando_documentos_e_conselho(): void
    {
        $usuario = User::factory()->create();

        $profissional = app(SalvarProfissional::class)->executar([
            'user_id' => $usuario->id,
            'nome' => '  Maria da Silva  ',
            'cpf' => '123.456.789-01',
            'cns' => '123 4567 8901 2345',
            'categoria' => '  Médica  ',
            'cbo' => '2251-25',
            'conselho_tipo' => 'crm',
            'conselho_numero' => ' 12345 ',
            'conselho_uf' => 'rs',
        ]);

        $this->assertDatabaseHas('profissionais', [
            'id' => $profissional->id,
            'user_id' => $usuario->id,
            'nome' => 'Maria da Silva',
            'cpf' => '12345678901',
            'cns' => '123456789012345',
            'categoria' => 'médica',
            'cbo' => '225125',
            'conselho_tipo' => 'CRM',
            'conselho_numero' => '12345',
            'conselho_uf' => 'RS',
            'ativo' => true,
        ]);
    }

    public function test_atualiza_profissional_existente(): void
    {
        $profissional = Profissional::query()->create([
            'nome' => 'Nome antigo',
            'categoria' => 'enfermagem',
            'ativo' => true,
        ]);

        $atualizado = app(SalvarProfissional::class)->executar([
            'nome' => 'Nome atualizado',
            'categoria' => 'medicina',
            'ativo' => false,
        ], $profissional);

        $this->assertSame($profissional->id, $atualizado->id);
        $this->assertFalse($atualizado->ativo);
        $this->assertDatabaseHas('profissionais', [
            'id' => $profissional->id,
            'nome' => 'Nome atualizado',
            'categoria' => 'medicina',
            'ativo' => false,
        ]);
    }

    public function test_cria_vinculo_com_unidade_e_valida_intervalo(): void
    {
        $organizacao = OrganizacaoSaude::query()->create([
            'nome' => 'Secretaria Municipal de Saúde',
            'sigla' => 'SMS',
            'ativo' => true,
        ]);
        $unidade = UnidadeSaude::query()->create([
            'organizacao_saude_id' => $organizacao->id,
            'nome' => 'Ambulatório Central',
            'tipo' => 'ambulatorio',
            'ativo' => true,
        ]);
        $profissional = Profissional::query()->create([
            'nome' => 'João Souza',
            'categoria' => 'enfermagem',
            'ativo' => true,
        ]);

        $vinculo = app(SalvarVinculoProfissionalUnidade::class)->executar(
            $profissional,
            $unidade,
            '2026-07-01',
            '2026-12-31',
        );

        $this->assertDatabaseHas('vinculos_profissionais_unidades', [
            'id' => $vinculo->id,
            'profissional_id' => $profissional->id,
            'unidade_saude_id' => $unidade->id,
            'ativo' => true,
        ]);
        $this->assertSame('2026-07-01', $vinculo->vigente_de?->toDateString());
        $this->assertSame('2026-12-31', $vinculo->vigente_ate?->toDateString());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A data final do vínculo não pode ser anterior à data inicial.');

        app(SalvarVinculoProfissionalUnidade::class)->executar(
            $profissional,
            $unidade,
            '2026-12-31',
            '2026-07-01',
        );
    }

    public function test_catalogo_inclui_permissoes_de_profissionais(): void
    {
        $this->seed(CatalogoAutorizacaoSeeder::class);

        $this->assertTrue(Permissao::query()->where('chave', 'profissionais.visualizar')->exists());
        $this->assertTrue(Permissao::query()->where('chave', 'profissionais.administrar')->exists());

        $gestor = Perfil::query()->where('chave', 'gestor_unidade')->firstOrFail();

        $this->assertTrue($gestor->permissoes()->where('chave', 'profissionais.visualizar')->exists());
        $this->assertTrue($gestor->permissoes()->where('chave', 'profissionais.administrar')->exists());
    }
}
