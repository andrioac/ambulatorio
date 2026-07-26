<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Jobs\EnviarLinkRecuperacaoSenha;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegraSenha;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class RecuperacaoSenhaController extends Controller
{
    public function solicitar(): Response
    {
        return Inertia::render('Autenticacao/EsqueciSenha');
    }

    public function enviarLink(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
        ]);

        EnviarLinkRecuperacaoSenha::dispatch(strtolower($dados['email']));

        return back()->with(
            'status',
            'Sua solicitação foi recebida. Se existir uma conta ativa para este e-mail, enviaremos as instruções. Caso não receba, tente novamente após 60 segundos.',
        );
    }

    public function redefinir(Request $request, string $token): Response
    {
        return Inertia::render('Autenticacao/RedefinirSenha', [
            'token' => $token,
            'email' => (string) $request->query('email'),
        ]);
    }

    public function atualizar(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $dados = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegraSenha::defaults()],
        ]);

        $status = Password::reset(
            $dados,
            function (User $usuario, string $senha) use ($auditoria): void {
                if (! $usuario->ativo) {
                    throw ValidationException::withMessages([
                        'email' => 'Não foi possível redefinir a senha desta conta.',
                    ]);
                }

                $usuario->forceFill([
                    'password' => Hash::make($senha),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));

                $auditoria->registrar('autenticacao.senha_redefinida', [
                    'entidade_tipo' => User::class,
                    'entidade_id' => $usuario->id,
                    'dados_posteriores' => ['email' => $usuario->email],
                ]);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'O link de recuperação é inválido ou expirou.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Senha redefinida com sucesso. Você já pode entrar.');
    }
}
