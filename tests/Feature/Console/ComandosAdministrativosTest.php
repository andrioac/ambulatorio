<?php

namespace Tests\Feature\Console;

use App\Models\AtribuicaoPerfil;
use App\Models\Perfil;
use App\Models\User;
use Database\Seeders\CatalogoAutorizacaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComandosAdministrativosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_usuario_ativo_por_padrao(): void
    {
        $this->artisan('ambulatorio:criar-usuario', [
            'nome' => 'Administrador Inicial',
            'email' => 'ADMIN@EXAMPLE.COM',
            '--senha' => 'Senha1234',
        ])->assertSuccessful();

        $usuario = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($usuario->ativo);
        $this->assertTrue(Hash::check('Senha1234', $usuario->password));
    }

    public function test_rejeita_email_duplicado(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('ambulatorio:criar-usuario', [
            'nome' => 'Outro Administrador',
            'email' => 'admin@example.com',
            '--senha' => 'Senha1234',
        ])->assertExitCode(2);

        $this->assertSame(1, User::query()->where('email', 'admin@example.com')->count());
    }

    public function test_atribui_superadministrador_de_forma_idempotente(): void
    {
        $this->seed(CatalogoAutorizacaoSeeder::class);
        $usuario = User::factory()->create(['email' => 'admin@example.com', 'ativo' => true]);

        $this->artisan('ambulatorio:atribuir-superadministrador', [
            'email' => 'ADMIN@EXAMPLE.COM',
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('ambulatorio:atribuir-superadministrador', [
            'email' => 'admin@example.com',
            '--force' => true,
        ])->assertSuccessful();

        $perfil = Perfil::query()->where('chave', 'superadministrador_sistema')->firstOrFail();

        $this->assertSame(1, AtribuicaoPerfil::query()
            ->where('user_id', $usuario->id)
            ->where('perfil_id', $perfil->id)
            ->where('tipo_escopo', 'sistema')
            ->where('ativo', true)
            ->count());
    }

    public function test_nao_atribui_superadministrador_a_usuario_inativo(): void
    {
        $this->seed(CatalogoAutorizacaoSeeder::class);
        User::factory()->create(['email' => 'inativo@example.com', 'ativo' => false]);

        $this->artisan('ambulatorio:atribuir-superadministrador', [
            'email' => 'inativo@example.com',
            '--force' => true,
        ])->assertExitCode(2);

        $this->assertDatabaseCount('atribuicoes_perfil', 0);
    }
}
