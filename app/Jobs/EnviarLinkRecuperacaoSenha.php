<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

final class EnviarLinkRecuperacaoSenha implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $email)
    {
    }

    public function handle(): void
    {
        $usuarioAtivo = User::query()
            ->where('email', $this->email)
            ->where('ativo', true)
            ->exists();

        if (! $usuarioAtivo) {
            return;
        }

        Password::sendResetLink(['email' => $this->email]);
    }
}
