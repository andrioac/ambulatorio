<?php

namespace Tests\Feature\Autenticacao;

use App\Jobs\EnviarLinkRecuperacaoSenha;
use App\Models\RegistroAuditoria;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_tela_de_solicitacao(): void
    {
        $this->get('/esqueci-minha-senha')->assertOk();
    }

    public function test_solicitacao_despacha_job_sem_expor_existencia_da_conta(): void
    {
        Queue::fake();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->post('/esqueci-minha-senha', ['email' => $usuario->email])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $mensagem) => str_contains($mensagem, '60 segundos'));

        Queue::assertPushed(
            EnviarLinkRecuperacaoSenha::class,
            fn (EnviarLinkRecuperacaoSenha $job) => $job->email === strtolower($usuario->email),
        );
    }

    public function test_resposta_e_generica_para_email_inexistente(): void
    {
        Queue::fake();

        $this->post('/esqueci-minha-senha', ['email' => 'inexistente@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Queue::assertPushed(EnviarLinkRecuperacaoSenha::class);
    }

    public function test_job_envia_notificacao_apenas_para_usuario_ativo(): void
    {
        Notification::fake();
        $usuario = User::factory()->create(['ativo' => true]);

        (new EnviarLinkRecuperacaoSenha($usuario->email))->handle();

        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    public function test_redefine_senha_com_token_valido_e_registra_auditoria(): void
    {
        Notification::fake();
        $usuario = User::factory()->create(['ativo' => true]);

        (new EnviarLinkRecuperacaoSenha($usuario->email))->handle();

        $token = null;
        Notification::assertSentTo($usuario, ResetPassword::class, function (ResetPassword $notificacao) use (&$token): bool {
            $token = $notificacao->token;

            return true;
        });

        $this->post('/redefinir-senha', [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NovaSenha123!', $usuario->fresh()->password));
        $this->assertDatabaseHas('registros_auditoria', [
            'evento' => 'autenticacao.senha_redefinida',
            'entidade_tipo' => User::class,
            'entidade_id' => $usuario->id,
        ]);
        $this->assertSame(1, RegistroAuditoria::query()->where('evento', 'autenticacao.senha_redefinida')->count());
    }

    public function test_rejeita_token_invalido(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

        $this->from('/redefinir-senha/token-invalido?email='.$usuario->email)
            ->post('/redefinir-senha', [
                'token' => 'token-invalido',
                'email' => $usuario->email,
                'password' => 'NovaSenha123!',
                'password_confirmation' => 'NovaSenha123!',
            ])
            ->assertSessionHasErrors('email');
    }
}
