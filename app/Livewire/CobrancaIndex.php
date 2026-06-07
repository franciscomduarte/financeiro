<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\DisparadorCobrancaMensalJob;
use App\Mail\CobrancaMensalMail;
use App\Models\Cobranca;
use App\Models\Paciente;
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
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'status', except: '')]
    public string $filtroStatus = '';

    #[Url(as: 'mes', except: '')]
    public string $filtroMes = '';

    // ─── Disparar manual ────────────────────────────────────────
    public bool    $modalDisparar     = false;
    public string  $pacienteDispararId = '';
    public string  $pacienteDispararNome = '';
    public bool    $dispararWhatsapp  = true;
    public bool    $dispararEmail     = true;

    // ─── Reenviar ───────────────────────────────────────────────
    public bool    $modalReenviar     = false;
    public ?int    $cobrancaReenviarId = null;
    public bool    $reenviarWhatsapp  = true;
    public bool    $reenviarEmail     = true;

    // ─── Flash ─────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function updatingBusca(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroMes(): void
    {
        $this->resetPage();
    }

    // ─── Confirmar disparar todas ───────────────────────────────
    public bool $modalDispararTodas = false;

    public function abrirModalDispararTodas(): void
    {
        $this->modalDispararTodas = true;
    }

    // ─── Disparar para todos os ativos ──────────────────────────
    public function dispararTodas(): void
    {
        $this->modalDispararTodas = false;
        $pacientes = Paciente::where('status', 'ativo')
            ->where('forma_pagamento', 'pix')
            ->where('valor_mensalidade', '>', 0)
            ->get(['id', 'nome']);

        if ($pacientes->isEmpty()) {
            $this->flashErro = 'Nenhum paciente ativo com mensalidade PIX cadastrada.';
            return;
        }

        foreach ($pacientes as $paciente) {
            DisparadorCobrancaMensalJob::dispatch($paciente->id)->onQueue('cobrancas');
        }

        $this->flashSucesso = "Cobranças agendadas para {$pacientes->count()} paciente(s). Serão processadas em breve.";
    }

    // ─── Abrir modal disparar manual ────────────────────────────
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
            $this->flashErro = 'Erro ao agendar cobrança: ' . $e->getMessage();
        }
    }

    // ─── Reenviar cobrança existente ────────────────────────────
    public function abrirModalReenviar(int $cobrancaId): void
    {
        $cobranca = Cobranca::with('paciente:id,nome,telefone,email')->findOrFail($cobrancaId);

        if ($cobranca->isPago()) {
            $this->flashErro = 'Esta cobrança já foi paga.';
            return;
        }

        $this->cobrancaReenviarId = $cobrancaId;
        $this->reenviarWhatsapp   = (bool) $cobranca->paciente?->telefone;
        $this->reenviarEmail      = (bool) $cobranca->paciente?->email;
        $this->modalReenviar      = true;
    }

    public function confirmarReenviar(WhatsAppService $whatsapp, \App\Services\AsaasService $asaas): void
    {
        try {
            $cobranca = Cobranca::with('paciente')->findOrFail($this->cobrancaReenviarId);

            // Se o QR Code não foi salvo (job criou antes do fix de retry), busca agora
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

            if ($this->reenviarWhatsapp && $cobranca->paciente?->telefone) {
                $whatsapp->enviarCobranca($cobranca->paciente->telefone, $dados, $cobranca->paciente->nome);
                $cobranca->update(['whatsapp_enviado_em' => now()]);
            }

            if ($this->reenviarEmail && $cobranca->paciente?->email) {
                Mail::to($cobranca->paciente->email)
                    ->send(new CobrancaMensalMail($cobranca->paciente, $dados));
                $cobranca->update(['email_enviado_em' => now()]);
            }

            $this->modalReenviar = false;
            $this->flashSucesso  = 'Cobrança reenviada com sucesso.';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao reenviar: ' . $e->getMessage();
        }
    }

    public function fecharModais(): void
    {
        $this->modalDispararTodas = false;
        $this->modalDisparar      = false;
        $this->modalReenviar      = false;
        $this->cobrancaReenviarId = null;
        $this->pacienteDispararId = '';
    }

    public function render(): View
    {
        $query = Cobranca::query()
            ->with('paciente:id,nome,telefone,email')
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

        $mesAtual   = Carbon::now()->format('Y-m');
        $totalMes   = Cobranca::where('mes_referencia', $mesAtual)->count();
        $pagosMes   = Cobranca::where('mes_referencia', $mesAtual)->where('status', 'RECEIVED')->count();
        $pendentesMes = Cobranca::where('mes_referencia', $mesAtual)->where('status', 'PENDING')->count();
        $vencidosMes  = Cobranca::where('mes_referencia', $mesAtual)->where('status', 'OVERDUE')->count();

        $pacientesAtivos = Paciente::where('status', 'ativo')
            ->where('forma_pagamento', 'pix')
            ->where('valor_mensalidade', '>', 0)
            ->count();

        return view('livewire.cobranca-index', compact(
            'cobrancas',
            'totalMes',
            'pagosMes',
            'pendentesMes',
            'vencidosMes',
            'pacientesAtivos',
            'mesAtual',
        ))->layout('layouts.app', ['title' => 'Cobranças']);
    }
}
