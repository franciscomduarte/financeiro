<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Comissoes\CalcularComissoesAction;
use App\Actions\Comissoes\DefinirPercentualComissaoAction;
use App\Actions\Comissoes\FecharComissaoAction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/** Comissões dos profissionais: percentual sobre o recebido, por mês, com fechamento lançado como despesa. */
class ComissaoIndex extends Component
{
    use Concerns\MensagemDeErro;

    #[Url(as: 'mes')]
    public string $competencia = '';

    /** @var array<string, string> percentual em edição, por profissional */
    public array $percentuais = [];

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->competencia)) {
            $this->competencia = now()->subMonthNoOverflow()->format('Y-m');
        }
    }

    public function mesAnterior(): void
    {
        $this->competencia = $this->mes()->subMonth()->format('Y-m');
    }

    public function mesSeguinte(): void
    {
        $this->competencia = $this->mes()->addMonth()->format('Y-m');
    }

    public function salvarPercentual(string $profissionalId, DefinirPercentualComissaoAction $definir): void
    {
        $this->flashSucesso = $this->flashErro = null;
        $valor = str_replace(',', '.', (string) ($this->percentuais[$profissionalId] ?? ''));

        if (! is_numeric($valor)) {
            $this->flashErro = 'Informe o percentual em número. Ex.: 30';

            return;
        }

        try {
            $p = $definir->execute($profissionalId, (float) $valor);
            unset($this->percentuais[$profissionalId]);
            $this->flashSucesso = "Comissão de {$p->nome} ajustada para " . rtrim(rtrim(number_format((float) $p->comissao_percentual, 2, ',', ''), '0'), ',') . '%.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o percentual');
        }
    }

    public function fechar(string $profissionalId, FecharComissaoAction $fechar): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $f = $fechar->execute($profissionalId, $this->competencia);
            $this->flashSucesso = 'Comissão fechada: R$ ' . number_format((float) $f->valor, 2, ',', '.') . ' lançados como despesa a pagar.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível fechar a comissão');
        }
    }

    private function mes(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', "{$this->competencia}-01");
    }

    public function render(CalcularComissoesAction $calcular): View
    {
        try {
            $linhas = $calcular->execute($this->competencia);
        } catch (Throwable) {
            $this->competencia = now()->subMonthNoOverflow()->format('Y-m');
            $linhas            = $calcular->execute($this->competencia);
        }

        return view('livewire.comissao-index', [
            'linhas'       => $linhas,
            'tituloMes'    => ucfirst($this->mes()->locale('pt_BR')->translatedFormat('F \d\e Y')),
            'mesEncerrado' => $this->competencia < now()->format('Y-m'),
            'totais'       => ['base' => $linhas->sum('base'), 'valor' => $linhas->sum('valor')],
        ])->layout('layouts.app', ['title' => 'Comissões']);
    }
}
