<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AutenticacaoController extends Controller
{
    public function criar(): Response
    {
        return Inertia::render('Autenticacao/Entrar');
    }

    public function armazenar(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $credenciais = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($credenciais, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'As credenciais informadas são inválidas.']);
        }

        if (! $request->user()?->ativo) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Este usuário está inativo.']);
        }

        $request->session()->regenerate();
        $auditoria->registrar('autenticacao.sucesso');

        return redirect()->intended('/');
    }

    public function destruir(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/entrar');
    }
}
