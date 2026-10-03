<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Services\AgendamentoService;
use App\Services\BloqueioAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BloqueioAgendaTest extends TestCase
{
    use RefreshDatabase;

    private BloqueioAgendaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BloqueioAgendaService::class);
    }

    public function test_bloqueio_para_varios_profissionais_compartilha_grupo_e_remove_junto(): void
    {
        $ana   = $this->profissional('Ana');
        $bruno = $this->profissional('Bruno');

        $resultado = $this->service->criar(
            [$ana->id, $bruno->id],
            CarbonImmutable::parse('2026-12-25'),
            CarbonImmutable::parse('2026-12-26'),
            true,
            'Natal',
        );

        $this->assertCount(2, $resultado['bloqueios']);
        $this->assertNotNull($resultado['bloqueios'][0]->grupo_id);
        $this->assertSame($resultado['bloqueios'][0]->grupo_id, $resultado['bloqueios'][1]->grupo_id);

        $this->service->remover($resultado['bloqueios'][0]);

        $this->assertSame(0, BloqueioAgenda::count());
    }

    public function test_bloqueio_individual_nao_tem_grupo(): void
    {
        $ana = $this->profissional('Ana');

        $resultado = $this->service->criar(
            [$ana->id],
            CarbonImmutable::parse('2026-10-05 14:00'),
            CarbonImmutable::parse('2026-10-05 16:00'),
            false,
            null,
        );

        $this->assertNull($resultado['bloqueios'][0]->grupo_id);
    }

    public function test_lista_apenas_agendamentos_pendentes_que_conflitam(): void
    {
        $ana      = $this->profissional('Ana');
        $bruno    = $this->profissional('Bruno');
        $conflito = $this->agendamento($ana, '2026-10-05 14:30', StatusAgendamento::Confirmado);
        $this->agendamento($ana, '2026-10-05 16:00', StatusAgendamento::Agendado);   // depois do bloqueio
        $this->agendamento($ana, '2026-10-05 15:00', StatusAgendamento::Cancelado);  // não está pendente
        $this->agendamento($bruno, '2026-10-05 14:30', StatusAgendamento::Agendado); // outro profissional

        $resultado = $this->service->criar(
            [$ana->id],
            CarbonImmutable::parse('2026-10-05 14:00'),
            CarbonImmutable::parse('2026-10-05 16:00'),
            false,
            'Congresso',
        );

        $this->assertSame([$conflito->id], $resultado['conflitos']->pluck('id')->all());
    }

    public function test_horarios_bloqueados_nao_ficam_disponiveis(): void
    {
        $ana = $this->profissional('Ana');
        $ana->gradeHorarios()->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fim' => '12:00', 'ativo' => true]);

        $this->service->criar(
            [$ana->id],
            CarbonImmutable::parse('2026-10-05 09:00'),
            CarbonImmutable::parse('2026-10-05 10:00'),
            false,
            null,
        );

        $slots = app(AgendamentoService::class)->slotsDisponiveis($ana->id, '2026-10-05', 30);

        $this->assertSame(['08:00', '08:30', '10:00', '10:30', '11:00', '11:30'], $slots);
    }

    private function profissional(string $nome): Profissional
    {
        $profissional     = new Profissional(['nome' => $nome, 'email' => Str::slug($nome) . '@teste.com', 'ativo' => true]);
        $profissional->id = (string) Str::uuid();
        $profissional->save();

        return $profissional;
    }

    private function agendamento(Profissional $profissional, string $inicio, StatusAgendamento $status): Agendamento
    {
        $procedimento = Procedimento::firstOrCreate(['nome' => 'Teste'], ['duracao_minutos' => 60, 'valor' => 100, 'ativo' => true]);
        $paciente     = Paciente::create(['nome' => 'Paciente ' . Str::random(4)]);

        return Agendamento::create([
            'paciente_id'     => $paciente->id,
            'profissional_id' => $profissional->id,
            'procedimento_id' => $procedimento->id,
            'inicio_em'       => $inicio,
            'fim_em'          => CarbonImmutable::parse($inicio)->addHour(),
            'status'          => $status->value,
        ]);
    }
}
