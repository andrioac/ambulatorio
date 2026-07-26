<?php

namespace App\Aplicacao\Autorizacao;

use App\Models\AtribuicaoPerfil;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AtribuirSuperadministradorInicial
{
    public function executar(string $email): AtribuicaoPerfil
    {
        $emailNormalizado = mb_strtolower(trim($email));

        return DB::transaction(function () use ($emailNormalizado): AtribuicaoPerfil {
            $usuario = User::query()
                ->whereRaw('LOWER(email) = ?', [$emailNormalizado])
                ->lockForUpdate()
                ->first();

            if ($usuario === null) {
                throw new \DomainException('Não existe usuário com o e-mail informado.');
            }

            if (! $usuario->ativo) {
                throw new \DomainException('O usuário informado está inativo.');
            }

            $perfil = Perfil::query()
                ->where('chave', 'superadministrador_sistema')
                ->where('protegido', true)
                ->where('ativo', true)
                ->first();

            if ($perfil === null) {
                throw new \DomainException('O perfil de superadministrador não está disponível. Execute as migrations e o seeder de autorização.');
            }

            $existente = AtribuicaoPerfil::query()
                ->where('user_id', $usuario->id)
                ->where('perfil_id', $perfil->id)
                ->where('tipo_escopo', 'sistema')
                ->whereNull('organizacao_saude_id')
                ->whereNull('unidade_saude_id')
                ->where('ativo', true)
                ->first();

            if ($existente !== null) {
                return $existente;
            }

            return AtribuicaoPerfil::query()->create([
                'user_id' => $usuario->id,
                'perfil_id' => $perfil->id,
                'tipo_escopo' => 'sistema',
                'organizacao_saude_id' => null,
                'unidade_saude_id' => null,
                'vigente_de' => null,
                'vigente_ate' => null,
                'ativo' => true,
            ]);
        });
    }
}
