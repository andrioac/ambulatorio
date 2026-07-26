<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class CriarUsuarioCommand extends Command
{
    protected $signature = 'ambulatorio:criar-usuario
        {nome? : Nome completo do usuário}
        {email? : E-mail do usuário}
        {--senha= : Senha inicial; quando omitida será solicitada de forma oculta}
        {--inativo : Cria o usuário inativo}';

    protected $description = 'Cria uma identidade de acesso ao Ambulatório Inteligente';

    public function handle(): int
    {
        $nome = trim((string) ($this->argument('nome') ?: $this->ask('Nome completo')));
        $email = mb_strtolower(trim((string) ($this->argument('email') ?: $this->ask('E-mail'))));
        $senha = (string) ($this->option('senha') ?: $this->secret('Senha inicial'));

        $validacao = Validator::make(
            ['nome' => $nome, 'email' => $email, 'senha' => $senha],
            [
                'nome' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
                'senha' => ['required', Password::min(8)->letters()->numbers()],
            ],
        );

        if ($validacao->fails()) {
            foreach ($validacao->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::INVALID;
        }

        $usuario = User::query()->create([
            'name' => $nome,
            'email' => $email,
            'password' => Hash::make($senha),
            'ativo' => ! $this->option('inativo'),
        ]);

        $this->info("Usuário {$usuario->name} <{$usuario->email}> criado com sucesso (ID {$usuario->id}).");

        return self::SUCCESS;
    }
}
