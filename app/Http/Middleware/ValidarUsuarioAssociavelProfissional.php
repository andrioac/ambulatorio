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
    public function __construct(private readonly AutorizadorEscopado $autorizador) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->integer('user_id') ?: null;

        if ($userId === null) {
            return $next($request);
        }

        $profissional = $request->route('profissional');

        if ($profissional instanceof Profissional && $profissional->user_id === $userId) {
            return $next($request);
        }

        $ator = $request->user();
        $escopos = $this->autorizador->escoposDiretos($ator, 'usuarios.visualizar');
        $consulta = User::query()->whereKey($userId)->where('ativo', true);

        if ($escopos['sistema'] === false) {
            $unidades = $this->autorizador->unidadesPermitidas($ator, 'usuarios.visualizar') ?? [];

            $consulta->whereHas('atribuicoesPerfil', function ($query) use ($escopos, $unidades): void {
                $query->where('ativo', true)
                    ->where(function ($query): void {
                        $query->whereNull('vigente_de')->orWhere('vigente_de', '<=', now());
                    })
                    ->where(function ($query): void {
                        $query->whereNull('vigente_ate')->orWhere('vigente_ate', '>=', now());
                    })
                    ->where(function ($query) use ($escopos, $unidades): void {
                        $query->where(function ($query) use ($escopos): void {
                            $query->where('tipo_escopo', 'organizacao')
                                ->whereIn('organizacao_saude_id', $escopos['organizacoes']);
                        })->orWhere(function ($query) use ($unidades): void {
                            $query->where('tipo_escopo', 'unidade')
                                ->whereIn('unidade_saude_id', $unidades);
                        });
                    });
            });
        }

        if ($consulta->exists() === false) {
            throw ValidationException::withMessages([
                'user_id' => 'O usuário selecionado não está ativo ou não pertence ao seu escopo autorizado.',
            ]);
        }

        return $next($request);
    }
}
