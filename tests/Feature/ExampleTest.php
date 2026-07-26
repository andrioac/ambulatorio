<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/')
            ->assertRedirect('/entrar');
    }
}
