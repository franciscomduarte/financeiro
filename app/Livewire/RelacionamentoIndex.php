<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Relacionamento\EnviarPesquisaAction;
use App\Actions\Relacionamento\RegistrarContatoAction;
use App\Enums\TipoContatoRelacionamento;
use App\Services\ListasRelacionamentoService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/** Relacionamento: listas do dia para a recepção chamar os pacientes (retorno, aniversário, sumidos, pesquisa). */
class RelacionamentoIndex extends Component
{
    use Concerns\MensagemDeErro;

    public const ABAS = ['retornos', 'aniversarios', 'sumidos', 'pesquisas'];

    #[Url(except: 'retornos')]
    public string $aba = 'retornos';

    #[Url(as: 'meses', except: 6)]
    public int $mesesSumido = 6;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $this->normalizar();
    }

    public function updatedAba(): void
    {
        $this->normalizar();
        $this->flashSucesso = $this->flashErro = null;
    }

    public function updatedMesesSumido(): void
    {
        $this->normalizar();
    }

    public function enviar(string $tipo, string $pacienteId, string $referencia, ?string $procedimento, RegistrarContatoAction $registrar): void
    {
        $this->contato($tipo, $pacienteId, $referencia, true, $procedimento, $registrar);
    }

    public function marcarFeito(string $tipo, string $pacienteId, string $referencia, RegistrarContatoAction $registrar): void
    {
        $this->contato($tipo, $pacienteId, $referencia, false, null, $registrar);
    }

    public function enviarPesquisa(string $agendamentoId, EnviarPesquisaAction $enviar): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $enviar->execute($agendamentoId);
            $this->flashSucesso = 'Pesquisa enviada por WhatsApp.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar a pesquisa');
        }
    }

    private function contato(string $tipo, string $pacienteId, string $referencia, bool $whatsapp, ?string $procedimento, RegistrarContatoAction $registrar): void
    {
        $this->flashSucesso = $this->flashErro = null;
        $tipoContato = TipoContatoRelacionamento::tryFrom($tipo);
        if ($tipoContato === null) {
            return;
        }

        try {
            $registrar->execute($tipoContato, $pacienteId, $referencia, $whatsapp, ['procedimento' => $procedimento]);
            $this->flashSucesso = $whatsapp ? 'Mensagem enviada por WhatsApp.' : 'Contato marcado como feito.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível registrar o contato');
        }
    }

    private function normalizar(): void
    {
        $this->aba         = in_array($this->aba, self::ABAS, true) ? $this->aba : 'retornos';
        $this->mesesSumido = in_array($this->mesesSumido, [3, 6, 12], true) ? $this->mesesSumido : 6;
    }

    public function render(ListasRelacionamentoService $listas): View
    {
        $dados = [
            'retornos'     => $listas->retornos(),
            'aniversarios' => $listas->aniversarios(),
            'sumidos'      => $listas->sumidos($this->mesesSumido),
            'semPesquisa'  => $listas->semPesquisa(),
        ];

        return view('livewire.relacionamento-index', [
            ...$dados,
            'resultado' => $this->aba === 'pesquisas' ? $listas->resultadoPesquisas() : null,
            'contagens' => [
                'retornos'     => $dados['retornos']->count(),
                'aniversarios' => $dados['aniversarios']->count(),
                'sumidos'      => $dados['sumidos']->count(),
                'pesquisas'    => $dados['semPesquisa']->count(),
            ],
        ])->layout('layouts.app', ['title' => 'Relacionamento']);
    }
}
