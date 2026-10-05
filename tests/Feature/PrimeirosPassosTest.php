<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\PrimeirosPassos;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PrimeirosPassosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_de_clinica_nova_ve_os_passos_e_o_progresso(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Paciente::create(['nome' => 'Maria']);

        Livewire::test(PrimeirosPassos::class)
            ->assertSee('Primeiros passos')
            ->assertSee('de 7.')
            ->assertSee('Cadastrar profissional');
    }

    public function test_usuario_comum_nao_ve(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        Livewire::test(PrimeirosPassos::class)->assertDontSee('Primeiros passos');
    }

    public function test_dispensar_esconde_para_sempre(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(PrimeirosPassos::class)->call('dispensar')->assertDontSee('Primeiros passos');

        $this->assertNotNull($this->clinica->fresh()->primeiros_passos_dispensado_em);
        Livewire::test(PrimeirosPassos::class)->assertDontSee('Primeiros passos');
    }

    public function test_some_quando_tudo_esta_feito(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->clinica->update(['logo_path' => 'x.png', 'evolution_instance' => 'i', 'evolution_api_key' => 'k']);

        $profissional     = new Profissional(['nome' => 'Ana', 'email' => 'ana@x.com', 'ativo' => true]);
        $profissional->id = (string) Str::uuid();
        $profissional->save();
        $procedimento = Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 60, 'valor' => 800, 'ativo' => true]);
        $paciente     = Paciente::create(['nome' => 'Maria']);
        \App\Models\TaxaCartao::create(['modalidade' => 'debito', 'percentual' => 1.5, 'ativo' => true]);
        \App\Models\Agendamento::create([
            'paciente_id' => $paciente->id, 'profissional_id' => $profissional->id, 'procedimento_id' => $procedimento->id,
            'inicio_em' => '2026-10-20 10:00', 'fim_em' => '2026-10-20 11:00', 'status' => 'agendado',
        ]);

        Livewire::test(PrimeirosPassos::class)->assertDontSee('Primeiros passos');
    }

    public function test_usuario_comum_nao_pode_dispensar(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        Livewire::test(PrimeirosPassos::class)->call('dispensar')->assertForbidden();
    }
}
