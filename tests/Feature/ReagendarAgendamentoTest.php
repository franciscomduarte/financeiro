<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusAgendamento;
use App\Livewire\AgendamentoIndex;
use App\Models\Agendamento;
use App\Models\GradeHorario;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AgendamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Reagendar: o próprio horário fica livre e dá para encaixar fora da lista, com aviso. */
class ReagendarAgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private Profissional $profissional;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->actingAs(User::factory()->create());

        $this->profissional     = new Profissional(['nome' => 'Ana', 'email' => 'ana@teste.com', 'ativo' => true]);
        $this->profissional->id = (string) Str::uuid();
        $this->profissional->save();

        // 12/10/2026 é segunda-feira
        GradeHorario::create(['profissional_id' => $this->profissional->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fim' => '12:00', 'ativo' => true]);
    }

    private function agendar(string $paciente, string $inicio, string $fim, array $procedimentos): Agendamento
    {
        return Agendamento::create([
            'paciente_id'       => Paciente::create(['nome' => $paciente, 'telefone' => '(61) 9' . random_int(1000, 9999) . '-' . random_int(1000, 9999)])->id,
            'profissional_id'   => $this->profissional->id,
            'procedimento_id'   => $procedimentos[0],
            'procedimentos_ids' => $procedimentos,
            'inicio_em'         => "2026-10-12 {$inicio}",
            'fim_em'            => "2026-10-12 {$fim}",
            'status'            => StatusAgendamento::Agendado->value,
        ]);
    }

    public function test_proprio_horario_fica_livre_e_encaixe_com_aviso(): void
    {
        $limpeza = Procedimento::create(['nome' => 'Limpeza', 'duracao_minutos' => 30, 'valor' => 200, 'ativo' => true]);
        $peeling = Procedimento::create(['nome' => 'Peeling', 'duracao_minutos' => 30, 'valor' => 300, 'ativo' => true]);
        $bia   = $this->agendar('Bia', '10:00', '11:00', [$limpeza->id, $peeling->id]);
        $this->agendar('Carla', '11:00', '12:00', [$limpeza->id]);

        $tela = Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalReagendar', $bia->id)
            ->set('reagendarData', '2026-10-12');
        // 10:00 (o da própria Bia) aparece; 10:30 bate na Carla
        $this->assertSame(['08:00', '08:30', '09:00', '09:30', '10:00'], $tela->get('horariosReagendar'));

        $tela->set('reagendarSlot', '11:00')
            ->assertSee('Já tem Carla das 11:00 às 12:00.')
            ->assertSee('Encaixar mesmo assim')
            ->call('confirmarReagendamento')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'Agendamento reagendado.');

        $novo = Agendamento::query()->where('agendamento_origem_id', $bia->id)->sole();
        $this->assertSame('2026-10-12 11:00', $novo->inicio_em->format('Y-m-d H:i'));
        $this->assertSame('2026-10-12 12:00', $novo->fim_em->format('Y-m-d H:i')); // mantém a duração (2 procedimentos)
        $this->assertSame([$limpeza->id, $peeling->id], $novo->procedimentos_ids);
        $this->assertSame(StatusAgendamento::Reagendado, $bia->fresh()->status);
    }

    public function test_motivos_do_conflito(): void
    {
        $proc = Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 60, 'valor' => 900, 'ativo' => true]);
        $bia  = $this->agendar('Bia', '09:00', '10:00', [$proc->id]);
        $servico = app(AgendamentoService::class);

        $this->assertNull($servico->conflito($this->profissional->id, '2026-10-12', '09:30', 60, $bia->id));
        $this->assertStringContainsString('Já tem Bia', (string) $servico->conflito($this->profissional->id, '2026-10-12', '09:30', 60));
        $this->assertStringContainsString('fora do horário', (string) $servico->conflito($this->profissional->id, '2026-10-12', '11:30', 60));
        $this->assertStringContainsString('não atende', (string) $servico->conflito($this->profissional->id, '2026-10-13', '09:00', 60));

        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalReagendar', $bia->id)
            ->set('reagendarData', '2026-10-12')
            ->set('reagendarSlot', '10:15')
            ->assertSee('Horário livre.')
            ->assertSee('Confirmar reagendamento')
            ->set('reagendarSlot', '25:00')
            ->call('confirmarReagendamento')
            ->assertHasErrors('reagendarSlot');
    }
}
