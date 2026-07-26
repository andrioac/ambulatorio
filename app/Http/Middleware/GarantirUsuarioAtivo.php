<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class GarantirUsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->ativo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Seu acesso foi inativado. Entre em contato com o administrador.');
        }

        return $next($request);
    }
}
