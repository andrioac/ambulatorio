<?php

namespace Tests\Feature\Autenticacao;

use App\Models\RegistroAuditoria;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_tela_de_solicitacao(): void
    {
        $this->get('/esqueci-minha-senha')->assertOk();
    }

    public function test_envia_link_para_usuario_existente_sem_expor_existencia_da_conta(): void
    {
        Notification::fake();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->post('/esqueci-minha-senha', ['email' => $usuario->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    public function test_resposta_e_generica_para_email_inexistente(): void
    {
        Notification::fake();

        $this->post('/esqueci-minha-senha', ['email' => 'inexistente@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_redefine_senha_com_token_valido_e_registra_auditoria(): void
    {
        Notification::fake();
        $usuario = User::factory()->create(['ativo' => true]);

        $this->post('/esqueci-minha-senha', ['email' => $usuario->email]);

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
