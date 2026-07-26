<?php

namespace Tests;

use App\Models\AtribuicaoPerfil;
use App\Models\Perfil;
use App\Models\User;
use Database\Seeders\CatalogoAutorizacaoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function criarUsuarioComPerfil(
        string $perfilChave = 'superadministrador_sistema',
        string $tipoEscopo = 'sistema',
        ?int $organizacaoId = null,
        ?int $unidadeId = null,
        array $atributos = [],
    ): User {
        $this->seed(CatalogoAutorizacaoSeeder::class);
        $usuario = User::factory()->create(['ativo' => true, ...$atributos]);
        $perfil = Perfil::query()->where('chave', $perfilChave)->firstOrFail();

        AtribuicaoPerfil::query()->create([
            'user_id' => $usuario->id,
            'perfil_id' => $perfil->id,
            'tipo_escopo' => $tipoEscopo,
            'organizacao_saude_id' => $organizacaoId,
            'unidade_saude_id' => $unidadeId,
            'ativo' => true,
        ]);

        return $usuario;
    }
}
