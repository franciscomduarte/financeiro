<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusAgendamento;
use App\Livewire\AgendamentoIndex;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AgendamentoDetalheTest extends TestCase
{
    use RefreshDatabase;

    private Agendamento $agendamento;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $profissional     = new Profissional(['nome' => 'Ana', 'email' => 'ana@teste.com', 'ativo' => true]);
        $profissional->id = (string) Str::uuid();
        $profissional->save();

        $procedimento = Procedimento::create(['nome' => 'Teste', 'duracao_minutos' => 60, 'valor' => 100, 'ativo' => true]);

        $this->agendamento = Agendamento::create([
            'paciente_id'     => Paciente::create(['nome' => 'Maria'])->id,
            'profissional_id' => $profissional->id,
            'procedimento_id' => $procedimento->id,
            'inicio_em'       => now()->addDay()->setTime(10, 0),
            'fim_em'          => now()->addDay()->setTime(11, 0),
            'status'          => StatusAgendamento::Agendado->value,
        ]);
    }

    public function test_detalhe_de_agendamento_pendente_mostra_acoes(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirDetalhe', $this->agendamento->id)
            ->assertSee('Confirmar presença')
            ->assertSee('Reagendar');
    }

    public function test_confirmar_pelo_detalhe_mantem_o_detalhe_aberto(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirDetalhe', $this->agendamento->id)
            ->call('confirmarAgendamento', $this->agendamento->id)
            ->assertSet('modalDetalhe', true)
            ->assertDontSee('Confirmar presença');

        $this->assertSame(StatusAgendamento::Confirmado, $this->agendamento->fresh()->status);
    }

    public function test_cancelar_pelo_detalhe_troca_para_o_modal_de_cancelamento(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirDetalhe', $this->agendamento->id)
            ->call('abrirModalCancelar', $this->agendamento->id)
            ->assertSet('modalDetalhe', false)
            ->assertSet('modalCancelar', true)
            ->assertSet('cancelarId', $this->agendamento->id);
    }

    public function test_agendamento_realizado_nao_mostra_acoes(): void
    {
        $this->agendamento->update(['status' => StatusAgendamento::Realizado->value]);

        Livewire::test(AgendamentoIndex::class)
            ->call('abrirDetalhe', $this->agendamento->id)
            ->assertDontSee('Registrar falta de');
    }
}
