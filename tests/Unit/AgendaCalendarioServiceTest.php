<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\StatusAgendamento;
use App\Enums\VisaoAgenda;
use App\Models\Agendamento;
use App\Services\AgendaCalendarioService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AgendaCalendarioServiceTest extends TestCase
{
    private AgendaCalendarioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AgendaCalendarioService();
    }

    // ─── periodo() ───────────────────────────────────────────────

    public function test_periodo_dia_cobre_apenas_a_data(): void
    {
        [$inicio, $fim] = $this->service->periodo(VisaoAgenda::Dia, CarbonImmutable::parse('2026-10-03 15:00'));

        $this->assertSame('2026-10-03 00:00', $inicio->format('Y-m-d H:i'));
        $this->assertSame('2026-10-04 00:00', $fim->format('Y-m-d H:i'));
    }

    public function test_periodo_semana_vai_de_segunda_a_domingo(): void
    {
        // 03/10/2026 é um sábado
        [$inicio, $fim] = $this->service->periodo(VisaoAgenda::Semana, CarbonImmutable::parse('2026-10-03'));

        $this->assertSame('2026-09-28', $inicio->toDateString());
        $this->assertSame('2026-10-05', $fim->toDateString());
    }

    public function test_periodo_mes_ocupa_semanas_completas(): void
    {
        [$inicio, $fim] = $this->service->periodo(VisaoAgenda::Mes, CarbonImmutable::parse('2026-10-15'));

        $this->assertSame('2026-09-28', $inicio->toDateString()); // segunda antes de 01/10
        $this->assertSame('2026-11-02', $fim->toDateString());    // dia após o domingo 01/11
        $this->assertSame(0, (int) $inicio->diffInDays($fim) % 7);
    }

    // ─── navegar() ───────────────────────────────────────────────

    public function test_navegar_avanca_e_volta_conforme_visao(): void
    {
        $ref = CarbonImmutable::parse('2026-01-31');

        $this->assertSame('2026-02-01', $this->service->navegar(VisaoAgenda::Dia, $ref, 1)->toDateString());
        $this->assertSame('2026-01-24', $this->service->navegar(VisaoAgenda::Semana, $ref, -1)->toDateString());
        $this->assertSame('2026-02-01', $this->service->navegar(VisaoAgenda::Mes, $ref, 1)->toDateString());
        $this->assertSame('2025-12-01', $this->service->navegar(VisaoAgenda::Mes, $ref, -1)->toDateString());
    }

    // ─── faixaHoraria() ──────────────────────────────────────────

    public function test_faixa_usa_padrao_sem_grade(): void
    {
        $this->assertSame([8 * 60, 19 * 60], $this->service->faixaHoraria(null, collect()));
    }

    public function test_faixa_usa_grade_e_arredonda_para_horas_cheias(): void
    {
        $this->assertSame([9 * 60, 18 * 60], $this->service->faixaHoraria([9 * 60 + 30, 17 * 60 + 30], collect()));
    }

    public function test_faixa_expande_para_agendamentos_fora_da_grade(): void
    {
        $agendamentos = collect([
            $this->agendamento('2026-10-05 07:30', '2026-10-05 08:15'),
            $this->agendamento('2026-10-05 19:00', '2026-10-05 20:30'),
        ]);

        $this->assertSame([7 * 60, 21 * 60], $this->service->faixaHoraria([9 * 60, 18 * 60], $agendamentos));
    }

    // ─── layoutDia() ─────────────────────────────────────────────

    public function test_layout_sem_sobreposicao_usa_largura_total(): void
    {
        $layout = $this->layout([
            ['09:00', '10:00'],
            ['10:00', '11:00'],
        ]);

        $this->assertSame([[0, 1], [0, 1]], $layout);
    }

    public function test_layout_divide_eventos_sobrepostos_em_colunas(): void
    {
        $layout = $this->layout([
            ['09:00', '10:00'],
            ['09:30', '10:30'],
            ['09:45', '10:15'],
            ['11:00', '12:00'], // novo grupo, volta à largura total
        ]);

        $this->assertSame([[0, 3], [1, 3], [2, 3], [0, 1]], $layout);
    }

    public function test_layout_reaproveita_coluna_livre_dentro_do_grupo(): void
    {
        $layout = $this->layout([
            ['09:00', '11:00'],
            ['09:00', '09:30'],
            ['09:30', '10:00'], // cabe na coluna 1, liberada às 09:30
        ]);

        $this->assertSame([[0, 2], [1, 2], [1, 2]], $layout);
    }

    // ─── corSegura() ─────────────────────────────────────────────

    public function test_cor_segura_rejeita_valores_invalidos(): void
    {
        $this->assertSame('#be123c', AgendaCalendarioService::corSegura('#be123c'));
        $this->assertSame('#8b5cf6', AgendaCalendarioService::corSegura(null));
        $this->assertSame('#8b5cf6', AgendaCalendarioService::corSegura('red; background: url(x)'));
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function agendamento(string $inicio, string $fim): Agendamento
    {
        return new Agendamento([
            'inicio_em' => $inicio,
            'fim_em'    => $fim,
            'status'    => StatusAgendamento::Agendado->value,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $horarios
     * @return list<array{0: int, 1: int}>  [coluna, colunas] na ordem de entrada
     */
    private function layout(array $horarios): array
    {
        $agendamentos = new Collection(array_map(
            fn (array $h) => $this->agendamento("2026-10-05 {$h[0]}", "2026-10-05 {$h[1]}"),
            $horarios,
        ));

        $porObjeto = [];
        foreach ($this->service->layoutDia($agendamentos) as $item) {
            $porObjeto[spl_object_id($item['agendamento'])] = [$item['coluna'], $item['colunas']];
        }

        return $agendamentos->map(fn (Agendamento $ag) => $porObjeto[spl_object_id($ag)])->all();
    }
}
