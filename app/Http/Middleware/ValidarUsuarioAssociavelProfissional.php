<?php

namespace App\Http\Middleware;

use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Models\Profissional;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class ValidarUsuarioAssociavelProfissional
{
    public function __construct(private readonly AutorizadorEscopado $autorizador)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->integer('user_id') ?: null;

        if (! $userId) {
            return $next($request);
        }

        $profissional = $request->route('profissional');

        if ($profissional instanceof Profissional && $profissional->user_id === $userId) {
            return $next($request);
        }

        $ator = $request->user();

        if ($this->autorizador->possuiEscopoSistema($ator, 'usuarios.visualizar')) {
            return $next($request);
        }

        $organizacoes = $this->autorizador->organizacoesPermitidas($ator, 'usuarios.visualizar') ?? [];
        $unidades = $this->autorizador->unidadesPermitidas($ator, 'usuarios.visualizar') ?? [];

        $permitido = User::query()
            ->whereKey($userId)
            ->where('ativo', true)
            ->whereHas('atribuicoesPerfil', function ($query) use ($organizacoes, $unidades): void {
                $query->where('ativo', true)
                    ->where(function ($query): void {
                        $query->whereNull('vigente_de')->orWhere('vigente_de', '<=', now());
                    })
                    ->where(function ($query): void {
                        $query->whereNull('vigente_ate')->orWhere('vigente_ate', '>=', now());
                    })
                    ->where(function ($query) use ($organizacoes, $unidades): void {
                        $query->where(function ($query) use ($organizacoes): void {
                            $query->where('tipo_escopo', 'organizacao')
                                ->whereIn('organizacao_saude_id', $organizacoes);
                        })->orWhere(function ($query) use ($unidades): void {
                            $query->where('tipo_escopo', 'unidade')
                                ->whereIn('unidade_saude_id', $unidades);
                        });
                    });
            })
            ->exists();

        if (! $permitido) {
            throw ValidationException::withMessages([
                'user_id' => 'O usuário selecionado não pertence ao seu escopo autorizado.',
            ]);
        }

        return $next($request);
    }
}
