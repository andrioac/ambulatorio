<?php

namespace App\Aplicacao\Autorizacao;

use App\Models\AtribuicaoPerfil;
use App\Models\User;

class AutorizadorEscopado
{
    public function permite(User $usuario, string $permissao, ?int $organizacaoId = null, ?int $unidadeId = null): bool
    {
        if (! $usuario->ativo) {
            return false;
        }

        return $usuario->atribuicoesPerfil()
            ->where('ativo', true)
            ->where(function ($query): void {
                $query->whereNull('vigente_de')->orWhere('vigente_de', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('vigente_ate')->orWhere('vigente_ate', '>=', now());
            })
            ->whereHas('perfil', function ($query) use ($permissao): void {
                $query->where('ativo', true)
                    ->whereHas('permissoes', fn ($q) => $q->where('chave', $permissao));
            })
            ->get()
            ->contains(fn (AtribuicaoPerfil $atribuicao) => $this->alcanca($atribuicao, $organizacaoId, $unidadeId));
    }

    private function alcanca(AtribuicaoPerfil $atribuicao, ?int $organizacaoId, ?int $unidadeId): bool
    {
        return match ($atribuicao->tipo_escopo) {
            'sistema' => true,
            'organizacao' => $organizacaoId !== null && $atribuicao->organizacao_saude_id === $organizacaoId,
            'unidade' => $unidadeId !== null && $atribuicao->unidade_saude_id === $unidadeId,
            default => false,
        };
    }
}
