<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CancelarParcelamentoAction;
use App\Actions\CreateParcelamentoAction;
use App\Jobs\DisparadorCobrancaMensalJob;
use App\Jobs\DisparadorParcelaJob;
use App\Mail\CobrancaMensalMail;
use App\Models\Cobranca;
use App\Models\Paciente;
use App\Models\Parcelamento;
use App\Services\AsaasService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CobrancaIndex extends Component
{
    use Concerns\MensagemDeErro;
    use WithPagination;

    // ─── Tab ─────────────────────────────────────────────────────
    #[Url(as: 'aba', except: 'mensalidades')]
    public string $abaAtiva = 'mensalidades';

    // ─── Filtros mensalidades ─────────────────────────────────────
    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'status', except: '')]
    public string $filtroStatus = '';

    #[Url(as: 'mes', except: '')]
    public string $filtroMes = '';

    // ─── Modal: disparar todas ────────────────────────────────────
    public bool $modalDispararTodas = false;

    // ─── Modal: disparar manual (mensalidade) ─────────────────────
    public bool   $modalDisparar        = false;
    public string $pacienteDispararId   = '';
    public string $pacienteDispararNome = '';
    public bool   $dispararWhatsapp     = true;
    public bool   $dispararEmail        = true;

    // ─── Modal: reenviar cobrança ─────────────────────────────────
    public bool  $modalReenviar       = false;
    public ?int  $cobrancaReenviarId  = null;
    public bool  $reenviarWhatsapp    = true;
    public bool  $reenviarEmail       = true;

    // ─── Modal: novo parcelamento ─────────────────────────────────
    public bool   $modalNovoParcelamento = false;
    public string $formPacienteId        = '';
    public string $formDescricao         = '';
    public string $formValorTotal        = '';
    public string $formValorParcela      = '';
    public string $formTotalParcelas     = '';
    public string $formDiaCobranca       = '';
    public string $formDataInicio        = '';

    // ─── Modal: cancelar parcelamento ─────────────────────────────
    public bool   $modalCancelarParcelamento  = false;
    public ?int   $parcelamentoCancelarId     = null;
    public string $parcelamentoCancelarNome   = '';

    // ─── Modal: disparar parcela manual ──────────────────────────
    public bool   $modalDispararParcela      = false;
    public ?int   $parcelamentoDispararId    = null;
    public string $parcelamentoDispararNome  = '';

    // ─── Flash ───────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    // ─── Resetar paginação ao filtrar ─────────────────────────────
    public function updatingBusca(): void       { $this->resetPage(); }
    public function updatingFiltroStatus(): void { $this->resetPage(); }
    public function updatingFiltroMes(): void    { $this->resetPage(); }

    // ═══════════════════════════════════════════════════════════════
    // MENSALIDADES
    // ═══════════════════════════════════════════════════════════════

    public function abrirModalDispararTodas(): void
    {
        $this->modalDispararTodas = true;
    }

    public function dispararTodas(): void
    {
        $this->modalDispararTodas = false;

        $pacientes = Paciente::where('status', 'ativo')
            ->where('forma_pagamento', 'pix')
            ->where('valor_mensalidade', '>', 0)
            ->get(['id', 'nome']);

        if ($pacientes->isEmpty()) {
            $this->flashErro = 'Nenhum paciente ativo tem mensalidade por PIX. Ajuste o cadastro do paciente para cobrar por aqui.';
            return;
        }

        foreach ($pacientes as $paciente) {
            DisparadorCobrancaMensalJob::dispatch($paciente->id)->onQueue('cobrancas');
        }

        $this->flashSucesso = "Cobranças a caminho para {$pacientes->count()} paciente(s). O envio leva alguns minutos.";
    }

    public function abrirModalDisparar(string $pacienteId): void
    {
        $paciente = Paciente::findOrFail($pacienteId);
        $this->pacienteDispararId   = $pacienteId;
        $this->pacienteDispararNome = $paciente->nome;
        $this->dispararWhatsapp     = (bool) $paciente->telefone;
        $this->dispararEmail        = (bool) $paciente->email;
        $this->modalDisparar        = true;
    }

    public function confirmarDisparar(): void
    {
        try {
            DisparadorCobrancaMensalJob::dispatch(
                pacienteId: $this->pacienteDispararId,
                enviarWhatsapp: $this->dispararWhatsapp,
                enviarEmail: $this->dispararEmail,
            )->onQueue('cobrancas');

            $this->modalDisparar = false;
            $this->flashSucesso  = "Cobrança agendada para {$this->pacienteDispararNome}.";
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar a cobrança');
        }
    }

    public function abrirModalReenviar(int $cobrancaId): void
    {
        $cobranca = Cobranca::with('paciente:id,nome,telefone,email')->findOrFail($cobrancaId);

        if ($cobranca->isPago()) {
            $this->flashErro = 'Esta cobrança já foi paga. Não é preciso reenviar.';
            return;
        }

        $this->cobrancaReenviarId = $cobrancaId;
        $this->reenviarWhatsapp   = (bool) $cobranca->paciente?->telefone;
        $this->reenviarEmail      = (bool) $cobranca->paciente?->email;
        $this->modalReenviar      = true;
    }

    public function confirmarReenviar(WhatsAppService $whatsapp, AsaasService $asaas): void
    {
        try {
            $cobranca = Cobranca::with(['paciente', 'parcelamento'])->findOrFail($this->cobrancaReenviarId);

            $qrCodeTexto = $cobranca->qr_code_texto;
            if (empty($qrCodeTexto) && $cobranca->asaas_id) {
                $qr = $asaas->buscarQrCode($cobranca->asaas_id);
                $qrCodeTexto = $qr['payload'] ?? null;
                if ($qrCodeTexto) {
                    $cobranca->update(['qr_code_texto' => $qrCodeTexto]);
                }
            }

            $dados = [
                'pagamento_id'   => $cobranca->asaas_id,
                'valor'          => $cobranca->valor,
                'vencimento'     => Carbon::parse($cobranca->vencimento)->format('d/m/Y'),
                'link_fatura'    => $cobranca->link_fatura,
                'qr_code_texto'  => $qrCodeTexto,
                'qr_code_base64' => null,
            ];

            if ($cobranca->parcelamento && $cobranca->numero_parcela) {
                $dados['parcela_info'] = "{$cobranca->numero_parcela}/{$cobranca->parcelamento->total_parcelas}";
                $dados['descricao']    = $cobranca->parcelamento->descricao;
            }

            if ($this->reenviarWhatsapp && $cobranca->paciente?->telefone) {
                $ok = $whatsapp->enviarCobranca($cobranca->paciente->telefone, $dados, $cobranca->paciente->nome);
                $cobranca->update(['whatsapp_enviado_em' => now()]);
                app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::Cobranca, \App\Enums\CanalNotificacao::WhatsApp, $cobranca->paciente, (string) $cobranca->paciente->telefone, 'Cobrança (reenvio)',
                    'Cobrança de R$ ' . number_format((float) $cobranca->valor, 2, ',', '.') . ' com vencimento em ' . $dados['vencimento'], $ok, $cobranca, $whatsapp->ultimoIdMensagem);
            }

            if ($this->reenviarEmail && $cobranca->paciente?->email) {
                Mail::to($cobranca->paciente->email)
                    ->send(new CobrancaMensalMail($cobranca->paciente, $dados));
                $cobranca->update(['email_enviado_em' => now()]);
                app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::Cobranca, \App\Enums\CanalNotificacao::Email, $cobranca->paciente, (string) $cobranca->paciente->email, 'Cobrança (reenvio)',
                    'Cobrança de R$ ' . number_format((float) $cobranca->valor, 2, ',', '.') . ' com vencimento em ' . $dados['vencimento'], true, $cobranca);
            }

            $this->modalReenviar = false;
            $this->flashSucesso  = 'Cobrança reenviada.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível reenviar');
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PARCELAMENTOS
    // ═══════════════════════════════════════════════════════════════

    public function abrirModalNovoParcelamento(): void
    {
        $this->formPacienteId    = '';
        $this->formDescricao     = '';
        $this->formValorTotal    = '';
        $this->formValorParcela  = '';
        $this->formTotalParcelas = '';
        $this->formDiaCobranca   = '';
        $this->formDataInicio    = Carbon::now()->format('Y-m-d');
        $this->resetValidation();
        $this->modalNovoParcelamento = true;
    }

    public function salvarParcelamento(CreateParcelamentoAction $action): void
    {
        $dados = $this->validate([
            'formPacienteId'    => 'required|exists:pacientes,id',
            'formDescricao'     => 'required|string|max:200',
            'formValorTotal'    => 'required|numeric|min:0.01',
            'formValorParcela'  => 'required|numeric|min:0.01',
            'formTotalParcelas' => 'required|integer|min:2|max:999',
            'formDiaCobranca'   => 'required|integer|min:1|max:28',
            'formDataInicio'    => 'required|date',
        ]);

        try {
            $action->execute([
                'paciente_id'    => $dados['formPacienteId'],
                'descricao'      => $dados['formDescricao'],
                'valor_total'    => $dados['formValorTotal'],
                'valor_parcela'  => $dados['formValorParcela'],
                'total_parcelas' => (int) $dados['formTotalParcelas'],
                'dia_cobranca'   => (int) $dados['formDiaCobranca'],
                'data_inicio'    => $dados['formDataInicio'],
            ]);

            $this->modalNovoParcelamento = false;
            $this->flashSucesso = 'Parcelamento salvo.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o parcelamento');
        }
    }

    public function abrirModalCancelarParcelamento(int $id): void
    {
        $parcelamento = Parcelamento::with('paciente:id,nome')->findOrFail($id);
        $this->parcelamentoCancelarId   = $id;
        $this->parcelamentoCancelarNome = "{$parcelamento->descricao} ({$parcelamento->paciente?->nome})";
        $this->modalCancelarParcelamento = true;
    }

    public function confirmarCancelarParcelamento(CancelarParcelamentoAction $action): void
    {
        try {
            $parcelamento = Parcelamento::findOrFail($this->parcelamentoCancelarId);
            $action->execute($parcelamento);
            $this->modalCancelarParcelamento = false;
            $this->flashSucesso = 'Parcelamento cancelado.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e);
            $this->modalCancelarParcelamento = false;
        }
    }

    public function abrirModalDispararParcela(int $id): void
    {
        $parcelamento = Parcelamento::with('paciente:id,nome')->findOrFail($id);

        if (! $parcelamento->podeDisparar()) {
            $this->flashErro = 'Este parcelamento não tem mais parcelas para enviar.';
            return;
        }

        $num = $parcelamento->proximaNumeroParcela();
        $this->parcelamentoDispararId   = $id;
        $this->parcelamentoDispararNome = "Parcela {$num}/{$parcelamento->total_parcelas} — {$parcelamento->descricao} ({$parcelamento->paciente?->nome})";
        $this->modalDispararParcela     = true;
    }

    public function confirmarDispararParcela(): void
    {
        try {
            DisparadorParcelaJob::dispatch($this->parcelamentoDispararId)->onQueue('cobrancas');
            $this->modalDispararParcela = false;
            $this->flashSucesso = 'Parcela a caminho. O envio leva alguns minutos.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar a parcela');
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // UTILITÁRIOS
    // ═══════════════════════════════════════════════════════════════

    public function fecharModais(): void
    {
        $this->modalDispararTodas        = false;
        $this->modalDisparar             = false;
        $this->modalReenviar             = false;
        $this->modalNovoParcelamento     = false;
        $this->modalCancelarParcelamento = false;
        $this->modalDispararParcela      = false;
        $this->cobrancaReenviarId        = null;
        $this->pacienteDispararId        = '';
        $this->parcelamentoCancelarId    = null;
        $this->parcelamentoDispararId    = null;
    }

    public function render(): View
    {
        $mesAtual = Carbon::now()->format('Y-m');

        // ─── Dados mensalidades ───────────────────────────────────
        $query = Cobranca::query()
            ->with('paciente:id,nome,telefone,email')
            ->with('parcelamento:id,total_parcelas,descricao')
            ->orderByDesc('created_at');

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }

        if ($this->filtroMes !== '') {
            $query->where('mes_referencia', $this->filtroMes);
        }

        if ($this->busca !== '') {
            $term = $this->busca;
            $query->whereHas('paciente', fn ($q) => $q->where('nome', 'ilike', "%{$term}%"));
        }

        $cobrancas = $query->paginate(25);

        $totalMes     = Cobranca::where('mes_referencia', $mesAtual)->count();
        $pagosMes     = Cobranca::where('mes_referencia', $mesAtual)->where('status', 'RECEIVED')->count();
        $pendentesMes = Cobranca::where('mes_referencia', $mesAtual)->where('status', 'PENDING')->count();
        $vencidosMes  = Cobranca::where('mes_referencia', $mesAtual)->where('status', 'OVERDUE')->count();

        $pacientesAtivos = Paciente::where('status', 'ativo')
            ->where('forma_pagamento', 'pix')
            ->where('valor_mensalidade', '>', 0)
            ->count();

        // ─── Dados parcelamentos ──────────────────────────────────
        $parcelamentos = Parcelamento::with(['paciente:id,nome', 'cobrancas:id,parcelamento_id,status'])
            ->orderByRaw("CASE status WHEN 'ativo' THEN 0 WHEN 'concluido' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        $pacientesSelect = Paciente::where('status', 'ativo')
            ->select(['id', 'nome'])
            ->orderBy('nome')
            ->get();

        return view('livewire.cobranca-index', compact(
            'cobrancas',
            'totalMes',
            'pagosMes',
            'pendentesMes',
            'vencidosMes',
            'pacientesAtivos',
            'mesAtual',
            'parcelamentos',
            'pacientesSelect',
        ))->layout('layouts.app', ['title' => 'Cobranças']);
    }
}
