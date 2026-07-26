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
        return $this->atribuicoesValidas($usuario, $permissao)->exists();
    }

    public function possuiEscopoSistema(User $usuario, string $permissao): bool
    {
        return $this->escoposDiretos($usuario, $permissao)['sistema'];
    }

    public function permite(User $usuario, string $permissao, ?int $organizacaoId = null, ?int $unidadeId = null): bool
    {
        return $this->atribuicoesValidas($usuario, $permissao)
            ->get()
            ->contains(fn (AtribuicaoPerfil $atribuicao) => $this->alcanca($atribuicao, $organizacaoId, $unidadeId));
    }

    public function exigir(User $usuario, string $permissao, ?int $organizacaoId = null, ?int $unidadeId = null): void
    {
        abort_unless($this->permite($usuario, $permissao, $organizacaoId, $unidadeId), 403);
    }

    /**
     * Escopos explicitamente atribuídos ao usuário, sem expandir unidade para organização.
     *
     * @return array{sistema: bool, organizacoes: array<int>, unidades: array<int>}
     */
    public function escoposDiretos(User $usuario, string $permissao): array
    {
        $atribuicoes = $this->atribuicoesValidas($usuario, $permissao)->get();

        return [
            'sistema' => $atribuicoes->contains('tipo_escopo', 'sistema'),
            'organizacoes' => $atribuicoes
                ->where('tipo_escopo', 'organizacao')
                ->pluck('organizacao_saude_id')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'unidades' => $atribuicoes
                ->where('tipo_escopo', 'unidade')
                ->pluck('unidade_saude_id')
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * Retorna null quando o usuário possui alcance de sistema. Para navegação e
     * contexto, inclui a organização-pai das unidades diretamente autorizadas.
     *
     * @return array<int>|null
     */
    public function organizacoesPermitidas(User $usuario, string $permissao): ?array
    {
        $escopos = $this->escoposDiretos($usuario, $permissao);

        if ($escopos['sistema']) {
            return null;
        }

        $organizacoes = collect($escopos['organizacoes']);

        if ($escopos['unidades'] !== []) {
            $organizacoes = $organizacoes->merge(
                UnidadeSaude::query()->whereKey($escopos['unidades'])->pluck('organizacao_saude_id'),
            );
        }

        return $organizacoes->filter()->unique()->values()->all();
    }

    /**
     * Retorna null quando o usuário possui alcance de sistema. Expande
     * atribuições de organização para todas as suas unidades.
     *
     * @return array<int>|null
     */
    public function unidadesPermitidas(User $usuario, string $permissao): ?array
    {
        $escopos = $this->escoposDiretos($usuario, $permissao);

        if ($escopos['sistema']) {
            return null;
        }

        $unidades = collect($escopos['unidades']);

        if ($escopos['organizacoes'] !== []) {
            $unidades = $unidades->merge(
                UnidadeSaude::query()->whereIn('organizacao_saude_id', $escopos['organizacoes'])->pluck('id'),
            );
        }

        return $unidades->filter()->unique()->values()->all();
    }

    private function atribuicoesValidas(User $usuario, string $permissao): HasMany
    {
        $atribuicoes = $usuario->atribuicoesPerfil();

        if (! $usuario->ativo) {
            return $atribuicoes->whereRaw('1 = 0');
        }

        return $atribuicoes
            ->where('ativo', true)
            ->where(function ($query): void {
                $query->whereNull('vigente_de')->orWhere('vigente_de', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('vigente_ate')->orWhere('vigente_ate', '>=', now());
            })
            ->where(function ($query): void {
                $query->where('tipo_escopo', 'sistema')
                    ->orWhere(function ($query): void {
                        $query->where('tipo_escopo', 'organizacao')
                            ->whereHas('organizacao', fn ($query) => $query->where('ativo', true));
                    })
                    ->orWhere(function ($query): void {
                        $query->where('tipo_escopo', 'unidade')
                            ->whereHas('unidade', fn ($query) => $query
                                ->where('ativo', true)
                                ->whereHas('organizacao', fn ($query) => $query->where('ativo', true)));
                    });
            })
            ->whereHas('perfil', function ($query) use ($permissao): void {
                $query->where('ativo', true)
                    ->whereHas('permissoes', fn ($query) => $query->where('chave', $permissao));
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
