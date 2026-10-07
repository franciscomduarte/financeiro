<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Livewire\FluxoCaixaIndex;
use App\Services\DreService;
use App\Services\FluxoCaixaService;
use App\Support\ClinicaAtual;
use App\Support\GeradorPdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** DRE e fluxo de caixa em PDF ou CSV (para o contador ou para guardar). */
class ExportarFinanceiroController extends Controller
{
    public function __invoke(Request $request, string $relatorio, string $formato, DreService $dre, FluxoCaixaService $fluxo, GeradorPdf $pdf): Response
    {
        abort_unless(in_array($formato, ['pdf', 'csv'], true), 404);
        $clinica = app(ClinicaAtual::class)->get();

        [$titulo, $arquivo, $dados, $linhasCsv] = match ($relatorio) {
            'dre'             => $this->dre($request, $dre),
            'fluxo-projetado' => $this->projetado($request, $fluxo),
            'fluxo-realizado' => $this->realizado($request, $fluxo),
            default           => abort(404),
        };

        if ($formato === 'csv') {
            $csv = "\xEF\xBB\xBF" . collect($linhasCsv)->map(fn (array $l) => implode(';', array_map(
                fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', $l
            )))->implode("\r\n");

            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$arquivo}.csv\"",
                'Cache-Control'       => 'private, no-store',
            ]);
        }

        return response($pdf->gerar('pdf.financeiro', ['titulo' => $titulo, 'clinica' => $clinica, 'linhas' => $linhasCsv] + $dados), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$arquivo}.pdf\"",
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    private function dre(Request $request, DreService $dre): array
    {
        $mes = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('mes')) ? CarbonImmutable::createFromFormat('!Y-m', (string) $request->query('mes')) : CarbonImmutable::today()->startOfMonth();
        $d   = $dre->mes($mes);

        return [
            'DRE · ' . ucfirst($mes->translatedFormat('F \d\e Y')),
            'dre-' . $mes->format('Y-m'),
            ['subtitulo' => 'Demonstrativo de resultado por competência', 'cabecalho' => ['Linha', 'Valor (R$)']],
            [['Linha', 'Valor (R$)'], ...$dre->paraCsv($d)->all()],
        ];
    }

    private function projetado(Request $request, FluxoCaixaService $fluxo): array
    {
        $dias = in_array((int) $request->query('dias'), FluxoCaixaIndex::HORIZONTES, true) ? (int) $request->query('dias') : 90;
        $p    = $fluxo->projetado($dias);
        $n    = fn (float $v) => number_format($v, 2, ',', '');

        $linhas = [['Semana', 'Entradas (R$)', 'Saídas (R$)', 'Saldo (R$)'], ['Saldo hoje', '', '', $n($p['saldo_hoje'])]];
        foreach ($p['semanas'] as $s) {
            $linhas[] = [$s['inicio']->format('d/m/Y') . ' a ' . $s['fim']->format('d/m/Y'), $n($s['entradas']), $n($s['saidas']), $n($s['saldo'])];
        }
        $linhas[] = ['Vencidos fora da projeção (a receber / a pagar)', $n($p['vencidos_receber']), $n($p['vencidos_pagar']), ''];

        return ["Fluxo de caixa projetado · {$dias} dias", "fluxo-projetado-{$dias}-dias", ['subtitulo' => 'Gerado em ' . now()->format('d/m/Y H:i')], $linhas];
    }

    private function realizado(Request $request, FluxoCaixaService $fluxo): array
    {
        $periodo = (string) $request->query('periodo');
        if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $inicio = CarbonImmutable::createFromFormat('!Y-m', $periodo);
            [$fim, $por, $rotulo] = [$inicio->endOfMonth(), 'dia', ucfirst($inicio->translatedFormat('F \d\e Y'))];
        } else {
            $ano    = preg_match('/^\d{4}$/', $periodo) ? (int) $periodo : (int) today()->format('Y');
            $inicio = CarbonImmutable::create($ano, 1, 1);
            [$fim, $por, $rotulo] = [CarbonImmutable::create($ano, 12, 31), 'mes', (string) $ano];
        }
        $r = $fluxo->realizado($inicio, $fim, $por);
        $n = fn (float $v) => number_format($v, 2, ',', '');

        $linhas = [[$por === 'dia' ? 'Dia' : 'Mês', 'Entradas (R$)', 'Saídas (R$)', 'Líquido (R$)', 'Saldo (R$)'], ['Saldo inicial', '', '', '', $n($r['saldo_inicial'])]];
        foreach ($r['periodos'] as $p) {
            if ($por === 'dia' && $p['entradas'] == 0 && $p['saidas'] == 0) {
                continue;
            }
            $linhas[] = [$p['rotulo'], $n($p['entradas']), $n($p['saidas']), $n($p['liquido']), $n($p['saldo'])];
        }
        $linhas[] = ['Total', $n($r['entradas']), $n($r['saidas']), $n($r['entradas'] - $r['saidas']), $n($r['saldo_final'])];

        return ["Fluxo de caixa realizado · {$rotulo}", 'fluxo-realizado-' . ($periodo ?: $inicio->format('Y')), ['subtitulo' => 'Regime de caixa (data em que o dinheiro entrou ou saiu)'], $linhas];
    }
}
