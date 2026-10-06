<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CobrancaMensalMail;
use App\Models\Cobranca;
use App\Models\Paciente;
use App\Services\AsaasService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DisparadorCobrancaMensalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $pacienteId,
        private readonly bool $enviarWhatsapp = true,
        private readonly bool $enviarEmail = true,
    ) {}

    public function handle(AsaasService $asaas, WhatsAppService $whatsapp): void
    {
        $paciente = Paciente::findOrFail($this->pacienteId);

        $mesAtual = Carbon::now()->format('Y-m');
        $jaExiste = Cobranca::where('paciente_id', $paciente->id)
            ->where('mes_referencia', $mesAtual)
            ->exists();

        if ($jaExiste) {
            Log::info("Cobrança de {$mesAtual} já existe para paciente {$paciente->id}. Pulando.");
            return;
        }

        $clienteAsaasId = $asaas->obterOuCriarCliente([
            'nome'     => $paciente->nome,
            'cpf'      => $paciente->cpf,
            'email'    => $paciente->email,
            'telefone' => $paciente->telefone,
        ]);

        $vencimento = Carbon::now()->addDays(5);
        $descricao  = "Mensalidade {$paciente->nome} - " . Carbon::now()->translatedFormat('F/Y');

        $cobranca = $asaas->gerarCobrancaPix(
            clienteId: $clienteAsaasId,
            valor: (float) $paciente->valor_mensalidade,
            descricao: $descricao,
            vencimento: $vencimento,
        );

        $registro = Cobranca::create([
            'paciente_id'   => $paciente->id,
            'asaas_id'      => $cobranca['pagamento_id'],
            'valor'         => $paciente->valor_mensalidade,
            'vencimento'    => $vencimento,
            'mes_referencia' => $mesAtual,
            'status'        => 'PENDING',
            'qr_code_texto' => $cobranca['qr_code_texto'],
            'link_fatura'   => $cobranca['link_fatura'],
        ]);

        if ($this->enviarWhatsapp && $paciente->telefone) {
            try {
                $ok = $whatsapp->enviarCobranca($paciente->telefone, $cobranca, $paciente->nome);
                $registro->update(['whatsapp_enviado_em' => now()]);
                app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::Cobranca, \App\Enums\CanalNotificacao::WhatsApp, $paciente, (string) $paciente->telefone, 'Cobrança ' . $mesAtual,
                    'Cobrança de R$ ' . number_format((float) $paciente->valor_mensalidade, 2, ',', '.') . ' com vencimento em ' . (string) $vencimento, $ok, $registro, $whatsapp->ultimoIdMensagem);
            } catch (Throwable $e) {
                Log::error("WhatsApp falhou para paciente {$paciente->id}: {$e->getMessage()}");
            }
        }

        if ($this->enviarEmail && $paciente->email) {
            try {
                Mail::to($paciente->email)->send(new CobrancaMensalMail($paciente, $cobranca));
                $registro->update(['email_enviado_em' => now()]);
                app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::Cobranca, \App\Enums\CanalNotificacao::Email, $paciente, (string) $paciente->email, 'Cobrança ' . $mesAtual,
                    'Cobrança de R$ ' . number_format((float) $paciente->valor_mensalidade, 2, ',', '.') . ' com vencimento em ' . (string) $vencimento, true, $registro);
            } catch (Throwable $e) {
                Log::error("E-mail falhou para paciente {$paciente->id}: {$e->getMessage()}");
            }
        }

        Log::info("Cobrança gerada: paciente {$paciente->id}, Asaas ID: {$cobranca['pagamento_id']}");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Job falhou para paciente {$this->pacienteId}: {$exception->getMessage()}");
    }
}
