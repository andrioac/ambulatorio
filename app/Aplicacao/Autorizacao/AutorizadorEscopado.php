<?php

namespace App\Aplicacao\Autorizacao;

use App\Models\AtribuicaoPerfil;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutorizadorEscopado
{
    public function possuiPermissao(User $usuario, string $permissao): bool
    {
        if (! $usuario->ativo) {
            return false;
        }

        return $this->atribuicoesValidas($usuario, $permissao)->exists();
    }

    public function possuiEscopoSistema(User $usuario, string $permissao): bool
    {
        return $this->atribuicoesValidas($usuario, $permissao)
            ->where('tipo_escopo', 'sistema')
            ->exists();
    }

    public function permite(User $usuario, string $permissao, ?int $organizacaoId = null, ?int $unidadeId = null): bool
    {
        if (! $usuario->ativo) {
            return false;
        }

        return $this->atribuicoesValidas($usuario, $permissao)
            ->get()
            ->contains(fn (AtribuicaoPerfil $atribuicao) => $this->alcanca($atribuicao, $organizacaoId, $unidadeId));
    }

    public function exigir(User $usuario, string $permissao, ?int $organizacaoId = null, ?int $unidadeId = null): void
    {
        abort_unless($this->permite($usuario, $permissao, $organizacaoId, $unidadeId), 403);
    }

    /**
     * Retorna null quando o usuário possui alcance de sistema.
     *
     * @return array<int>|null
     */
    public function organizacoesPermitidas(User $usuario, string $permissao): ?array
    {
        $atribuicoes = $this->atribuicoesValidas($usuario, $permissao)->get();

        if ($atribuicoes->contains('tipo_escopo', 'sistema')) {
            return null;
        }

        $organizacoes = $atribuicoes
            ->where('tipo_escopo', 'organizacao')
            ->pluck('organizacao_saude_id');

        $unidades = $atribuicoes
            ->where('tipo_escopo', 'unidade')
            ->pluck('unidade_saude_id')
            ->filter();

        if ($unidades->isNotEmpty()) {
            $organizacoes = $organizacoes->merge(
                UnidadeSaude::query()->whereKey($unidades)->pluck('organizacao_saude_id'),
            );
        }

        return $organizacoes->filter()->unique()->values()->all();
    }

    /**
     * Retorna null quando o usuário possui alcance de sistema.
     *
     * @return array<int>|null
     */
    public function unidadesPermitidas(User $usuario, string $permissao): ?array
    {
        $atribuicoes = $this->atribuicoesValidas($usuario, $permissao)->get();

        if ($atribuicoes->contains('tipo_escopo', 'sistema')) {
            return null;
        }

        $unidades = $atribuicoes
            ->where('tipo_escopo', 'unidade')
            ->pluck('unidade_saude_id');

        $organizacoes = $atribuicoes
            ->where('tipo_escopo', 'organizacao')
            ->pluck('organizacao_saude_id')
            ->filter();

        if ($organizacoes->isNotEmpty()) {
            $unidades = $unidades->merge(
                UnidadeSaude::query()->whereIn('organizacao_saude_id', $organizacoes)->pluck('id'),
            );
        }

        return $unidades->filter()->unique()->values()->all();
    }

    private function atribuicoesValidas(User $usuario, string $permissao): HasMany
    {
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
            });
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
