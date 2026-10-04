<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusAgendamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Livewire\AgendamentoIndex;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ConcluirAtendimentoTest extends TestCase
{
    use RefreshDatabase;

    private Agendamento $agendamento;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $profissional     = new Profissional(['nome' => 'Ana', 'email' => 'ana@teste.com', 'ativo' => true]);
        $profissional->id = (string) Str::uuid();
        $profissional->save();

        $botox    = Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 60, 'valor' => 850, 'ativo' => true]);
        $peeling  = Procedimento::create(['nome' => 'Peeling', 'duracao_minutos' => 30, 'valor' => 150, 'ativo' => true]);
        $this->paciente = Paciente::create(['nome' => 'Maria', 'forma_pagamento' => 'debito']);

        $this->agendamento = Agendamento::create([
            'paciente_id'       => $this->paciente->id,
            'profissional_id'   => $profissional->id,
            'procedimento_id'   => $botox->id,
            'procedimentos_ids' => [$botox->id, $peeling->id],
            'inicio_em'         => '2026-10-05 10:00',
            'fim_em'            => '2026-10-05 11:30',
            'status'            => StatusAgendamento::Confirmado->value,
        ]);
    }

    public function test_abrir_preenche_valor_dos_procedimentos_e_forma_do_paciente(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->assertSet('modalConcluir', true)
            ->assertSet('concluirValor', '1000.00')
            ->assertSet('concluirFormaPagamento', 'debito')
            ->assertSet('concluirLancarReceita', true);
    }

    public function test_concluir_lanca_receita_vinculada_ao_agendamento(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->set('concluirCategoria', 'Procedimento Facial')
            ->set('concluirFormaPagamento', 'pix')
            ->call('confirmarConclusao')
            ->assertHasNoErrors()
            ->assertSet('modalConcluir', false);

        $this->assertSame(StatusAgendamento::Realizado, $this->agendamento->fresh()->status);

        $receita = Transacao::where('agendamento_id', $this->agendamento->id)->sole();
        $this->assertSame(TipoTransacao::Entrada, $receita->tipo);
        $this->assertSame(StatusTransacao::Pago, $receita->status);
        $this->assertSame('1000.00', $receita->valor_bruto);
        $this->assertSame($this->paciente->id, $receita->paciente_id);
        $this->assertSame('2026-10-05', $receita->data_competencia->toDateString());
        $this->assertSame('Atendimento: Botox + Peeling', $receita->descricao);
    }

    public function test_a_receber_fica_pendente_sem_data_de_pagamento(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->set('concluirCategoria', 'Procedimento Facial')
            ->set('concluirPago', false)
            ->call('confirmarConclusao');

        $receita = Transacao::where('agendamento_id', $this->agendamento->id)->sole();
        $this->assertSame(StatusTransacao::Pendente, $receita->status);
        $this->assertNull($receita->data_pagamento);
    }

    public function test_categoria_e_obrigatoria(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->call('confirmarConclusao')
            ->assertHasErrors(['concluirCategoria' => 'required']);

        $this->assertSame(StatusAgendamento::Confirmado, $this->agendamento->fresh()->status);
        $this->assertSame(0, Transacao::count());
    }

    public function test_concluir_sem_receita(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->set('concluirLancarReceita', false)
            ->call('confirmarConclusao')
            ->assertHasNoErrors();

        $this->assertSame(StatusAgendamento::Realizado, $this->agendamento->fresh()->status);
        $this->assertSame(0, Transacao::count());
    }

    public function test_paciente_com_mensalidade_comeca_sem_lancar(): void
    {
        $this->paciente->update(['valor_mensalidade' => 300]);

        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->assertSet('concluirLancarReceita', false)
            ->assertSee('Paciente com mensalidade');
    }

    public function test_nao_conclui_duas_vezes(): void
    {
        $componente = Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $this->agendamento->id)
            ->set('concluirCategoria', 'Procedimento Facial');

        $componente->call('confirmarConclusao');
        // segunda tentativa (ex.: duplo clique / outra aba) não gera outra receita
        $componente->set('concluirId', $this->agendamento->id)
            ->set('concluirLancarReceita', true)
            ->call('confirmarConclusao');

        $this->assertSame(1, Transacao::where('agendamento_id', $this->agendamento->id)->count());
    }
}
