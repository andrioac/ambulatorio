<?php

namespace App\Console\Commands;

use App\Aplicacao\Autorizacao\AtribuirSuperadministradorInicial;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class AtribuirSuperadministradorCommand extends Command
{
    protected $signature = 'ambulatorio:atribuir-superadministrador
        {email? : E-mail do usuário ativo}
        {--force : Confirma a atribuição sem interação}';

    protected $description = 'Atribui o perfil protegido de superadministrador a um usuário ativo existente';

    public function handle(AtribuirSuperadministradorInicial $atribuir): int
    {
        $email = mb_strtolower(trim((string) ($this->argument('email') ?: $this->ask('E-mail do usuário'))));
        $validacao = Validator::make(['email' => $email], ['email' => ['required', 'email:rfc', 'max:255']]);

        if ($validacao->fails()) {
            $this->error('O e-mail informado é inválido.');

            return self::INVALID;
        }

        $usuario = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($usuario === null) {
            $this->error('Não existe usuário com o e-mail informado.');

            return self::INVALID;
        }

        $this->line("Usuário: {$usuario->name} <{$usuario->email}>");
        $this->warn('A operação concederá controle integral da instalação.');

        if (! $this->option('force') && ! $this->confirm('Confirmar atribuição de superadministrador?', false)) {
            $this->info('Operação cancelada. Nenhuma atribuição foi criada.');

            return self::SUCCESS;
        }

        try {
            $atribuicao = $atribuir->executar($email);
        } catch (\DomainException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable) {
            $this->error('Não foi possível atribuir o perfil. Nenhuma alteração foi persistida.');

            return self::FAILURE;
        }

        $this->info("Superadministrador atribuído com sucesso (atribuição {$atribuicao->id}).");

        return self::SUCCESS;
    }
}
