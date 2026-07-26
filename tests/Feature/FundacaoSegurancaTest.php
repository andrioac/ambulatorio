<?php

namespace Tests\Feature;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Models\RegistroAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundacaoSegurancaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_inativo_nao_consegue_entrar(): void
    {
        User::factory()->create(['email' => 'inativo@example.com', 'password' => 'senha-segura', 'ativo' => false]);

        $this->post('/entrar', ['email' => 'inativo@example.com', 'password' => 'senha-segura'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_resposta_possui_cabecalhos_defensivos(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($usuario)->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Cache-Control');
    }

    public function test_registrador_remove_segredos(): void
    {
        $registro = app(RegistradorAuditoria::class)->registrar('teste', ['password' => 'segredo', 'campo' => 'valor']);

        $this->assertSame('[REMOVIDO]', $registro->dados_posteriores['password']);
        $this->assertSame('valor', $registro->dados_posteriores['campo']);
        $this->assertDatabaseHas((new RegistroAuditoria)->getTable(), ['evento' => 'teste']);
    }
}
