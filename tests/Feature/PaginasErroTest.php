<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaginasErroTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_pagina_inertia_para_acesso_negado(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($usuario)->get('/profissionais')
            ->assertForbidden()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->component('Erro')
                ->where('status', 403));
    }

    public function test_exibe_pagina_inertia_para_rota_inexistente(): void
    {
        $usuario = User::factory()->create(['ativo' => true]);

        $this->actingAs($usuario)->get('/pagina-que-nao-existe')
            ->assertNotFound()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->component('Erro')
                ->where('status', 404));
    }
}
