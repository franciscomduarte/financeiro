<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_raiz_leva_visitante_para_o_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_raiz_leva_usuario_autenticado_para_o_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect('/dashboard');
    }
}
