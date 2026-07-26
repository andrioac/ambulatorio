<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class CatalogoAutorizacaoSeeder extends Seeder
{
    public function run(): void
    {
        $chaves = [
            'organizacoes.visualizar', 'organizacoes.administrar',
            'unidades.visualizar', 'unidades.administrar',
            'usuarios.visualizar', 'usuarios.administrar',
            'perfis.visualizar', 'perfis.administrar',
            'auditoria.visualizar',
            'pacientes.visualizar', 'pacientes.administrar',
            'fila.visualizar', 'fila.administrar',
            'triagem.realizar', 'prontuario.visualizar', 'prontuario.registrar',
            'prescricao.emitir',
        ];

        $permissoes = collect($chaves)->mapWithKeys(function (string $chave): array {
            $permissao = Permissao::query()->updateOrCreate(['chave' => $chave], ['descricao' => $chave]);

            return [$chave => $permissao];
        });

        $perfis = [
            'superadministrador_sistema' => $chaves,
            'administrador_organizacao' => array_values(array_filter($chaves, fn ($chave) => $chave !== 'organizacoes.administrar')),
            'gestor_unidade' => ['unidades.visualizar', 'usuarios.visualizar', 'pacientes.visualizar', 'fila.visualizar', 'fila.administrar', 'auditoria.visualizar'],
            'auditor_sistema' => ['auditoria.visualizar'],
        ];

        foreach ($perfis as $chave => $permissoesPerfil) {
            $perfil = Perfil::query()->updateOrCreate(['chave' => $chave], ['nome' => str($chave)->replace('_', ' ')->title(), 'protegido' => true, 'ativo' => true]);
            $perfil->permissoes()->sync(collect($permissoesPerfil)->map(fn ($permissao) => $permissoes[$permissao]->id));
        }
    }
}
