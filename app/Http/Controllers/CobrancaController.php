<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Scopes\ClinicaScope;
use App\Support\ClinicaAtual;
use App\Jobs\DisparadorCobrancaMensalJob;
use App\Mail\CobrancaMensalMail;
use App\Models\Cobranca;
use App\Models\Paciente;
use App\Services\AsaasService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CobrancaController extends Controller
{
    public function __construct(
        private readonly AsaasService $asaas,
    ) {}

    public function index(): JsonResponse
    {
        $cobrancas = Cobranca::with('paciente:id,nome,email,telefone')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($cobrancas);
    }

    public function dispararTodas(): JsonResponse
    {
        $pacientes = Paciente::where('status', 'ativo')
            ->where('forma_pagamento', 'pix')
            ->where('valor_mensalidade', '>', 0)
            ->get(['id']);

        foreach ($pacientes as $paciente) {
            DisparadorCobrancaMensalJob::dispatch($paciente->id)->onQueue('cobrancas');
        }

        return response()->json([
            'mensagem'   => "Cobranças agendadas para {$pacientes->count()} pacientes.",
            'disparados' => $pacientes->count(),
        ]);
    }

    public function dispararManual(Request $request, string $pacienteId): JsonResponse
    {
        $request->validate([
            'enviar_whatsapp' => 'boolean',
            'enviar_email'    => 'boolean',
        ]);

        $paciente = Paciente::findOrFail($pacienteId);

        DisparadorCobrancaMensalJob::dispatch(
            pacienteId: $paciente->id,
            enviarWhatsapp: $request->boolean('enviar_whatsapp', true),
            enviarEmail: $request->boolean('enviar_email', true),
        )->onQueue('cobrancas');

        return response()->json(['mensagem' => "Cobrança agendada para {$paciente->nome}."]);
    }

    public function reenviar(Request $request, int $cobrancaId): JsonResponse
    {
        $cobranca = Cobranca::with('paciente')->findOrFail($cobrancaId);

        if ($cobranca->isPago()) {
            return response()->json(['mensagem' => 'Esta cobrança já foi paga.'], 422);
        }

        $dados = [
            'pagamento_id'   => $cobranca->asaas_id,
            'valor'          => $cobranca->valor,
            'vencimento'     => Carbon::parse($cobranca->vencimento)->format('d/m/Y'),
            'link_fatura'    => $cobranca->link_fatura,
            'qr_code_texto'  => $cobranca->qr_code_texto,
            'qr_code_base64' => null,
        ];

        if ($request->boolean('whatsapp') && $cobranca->paciente?->telefone) {
            app(WhatsAppService::class)->enviarCobranca(
                $cobranca->paciente->telefone,
                $dados,
                $cobranca->paciente->nome,
            );
        }

        if ($request->boolean('email') && $cobranca->paciente?->email) {
            Mail::to($cobranca->paciente->email)
                ->send(new CobrancaMensalMail($cobranca->paciente, $dados));
        }

        return response()->json(['mensagem' => 'Cobrança reenviada com sucesso.']);
    }

    public function webhook(Request $request): JsonResponse
    {
        $tokenEsperado = config('asaas.webhook_token');

        if ($tokenEsperado && $request->header('asaas-access-token') !== $tokenEsperado) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $evento      = (string) $request->input('event', '');
        $pagamentoId = (string) $request->input('payment.id', '');

        if ($pagamentoId === '') {
            return response()->json(['ok' => true]);
        }

        $statusMap = [
            'PAYMENT_RECEIVED'  => 'RECEIVED',
            'PAYMENT_CONFIRMED' => 'RECEIVED',
            'PAYMENT_OVERDUE'   => 'OVERDUE',
            'PAYMENT_DELETED'   => 'DELETED',
            'PAYMENT_REFUNDED'  => 'REFUNDED',
        ];

        if (isset($statusMap[$evento])) {
            // Webhook não tem usuário logado: a clínica vem da própria cobrança
            $tenantId = Cobranca::withoutGlobalScope(ClinicaScope::class)->where('asaas_id', $pagamentoId)->value('tenant_id');
            $clinica  = $tenantId ? Clinica::find($tenantId) : null;

            if ($clinica) {
                app(ClinicaAtual::class)->executarComo($clinica, function () use ($pagamentoId, $evento, $statusMap): void {
                    $cobranca = Cobranca::with('parcelamento')->where('asaas_id', $pagamentoId)->firstOrFail();
                    $cobranca->update([
                        'status'  => $statusMap[$evento],
                        'pago_em' => in_array($evento, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true) ? now() : null,
                    ]);

                    if (in_array($evento, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true)) {
                        $cobranca->parcelamento?->verificarConclusao();
                    }
                });
            }
        }

        return response()->json(['ok' => true]);
    }

    public function sincronizarStatus(): JsonResponse
    {
        $pendentes = Cobranca::where('status', 'PENDING')
            ->where('vencimento', '>=', Carbon::now()->subDays(30))
            ->get();

        $atualizados = 0;

        foreach ($pendentes as $cobranca) {
            $status = $this->asaas->consultarStatus($cobranca->asaas_id);

            if ($status !== $cobranca->status) {
                $cobranca->update(['status' => $status]);
                $atualizados++;
            }
        }

        return response()->json([
            'mensagem'    => "{$atualizados} cobranças atualizadas.",
            'verificadas' => $pendentes->count(),
        ]);
    }
}
