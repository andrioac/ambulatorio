<?php

namespace App\Http\Middleware;

use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ExigirPermissao
{
    public function __construct(private readonly AutorizadorEscopado $autorizador)
    {
    }

    public function handle(Request $request, Closure $next, string $permissao): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && $this->autorizador->possuiPermissao($usuario, $permissao), 403);

        return $next($request);
    }
}
