<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Cobranca;
use App\Models\Parcelamento;
use App\Services\AsaasService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisparadorParcelaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $parcelamentoId,
        private readonly bool $enviarWhatsapp = true,
    ) {}

    public function handle(AsaasService $asaas, WhatsAppService $whatsapp): void
    {
        $parcelamento = Parcelamento::with('paciente')->findOrFail($this->parcelamentoId);
        $paciente     = $parcelamento->paciente;

        if (! $parcelamento->podeDisparar()) {
            Log::info("Parcelamento {$this->parcelamentoId} não pode disparar (status: {$parcelamento->status}, parcelas enviadas: {$parcelamento->parcelasEnviadas()}/{$parcelamento->total_parcelas}).");
            return;
        }

        $mesAtual      = Carbon::now()->format('Y-m');
        $numeroParcela = $parcelamento->proximaNumeroParcela();

        $jaExiste = Cobranca::where('parcelamento_id', $this->parcelamentoId)
            ->where('mes_referencia', $mesAtual)
            ->exists();

        if ($jaExiste) {
            Log::info("Parcela {$numeroParcela} do parcelamento {$this->parcelamentoId} já foi gerada em {$mesAtual}. Pulando.");
            return;
        }

        $clienteAsaasId = $asaas->obterOuCriarCliente([
            'nome'     => $paciente->nome,
            'cpf'      => $paciente->cpf,
            'email'    => $paciente->email,
            'telefone' => $paciente->telefone,
        ]);

        $vencimento = Carbon::now()->addDays(5);
        $descricao  = "{$parcelamento->descricao} - Parcela {$numeroParcela}/{$parcelamento->total_parcelas}";

        $cobranca = $asaas->gerarCobrancaPix(
            clienteId: $clienteAsaasId,
            valor: (float) $parcelamento->valor_parcela,
            descricao: $descricao,
            vencimento: $vencimento,
        );

        $registro = Cobranca::create([
            'paciente_id'     => $paciente->id,
            'parcelamento_id' => $parcelamento->id,
            'numero_parcela'  => $numeroParcela,
            'asaas_id'        => $cobranca['pagamento_id'],
            'valor'           => $parcelamento->valor_parcela,
            'vencimento'      => $vencimento,
            'mes_referencia'  => $mesAtual,
            'status'          => 'PENDING',
            'qr_code_texto'   => $cobranca['qr_code_texto'],
            'link_fatura'     => $cobranca['link_fatura'],
        ]);

        if ($this->enviarWhatsapp && $paciente->telefone) {
            try {
                $dadosWhatsapp = array_merge($cobranca, [
                    'parcela_info' => "{$numeroParcela}/{$parcelamento->total_parcelas}",
                    'descricao'    => $parcelamento->descricao,
                ]);
                $ok = $whatsapp->enviarCobranca($paciente->telefone, $dadosWhatsapp, $paciente->nome);
                $registro->update(['whatsapp_enviado_em' => now()]);
                app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::Cobranca, \App\Enums\CanalNotificacao::WhatsApp, $paciente, (string) $paciente->telefone, 'Parcela ' . $dadosWhatsapp['parcela_info'],
                    'Parcela ' . $dadosWhatsapp['parcela_info'] . ' de ' . $parcelamento->descricao, $ok, $registro, $whatsapp->ultimoIdMensagem);
            } catch (Throwable $e) {
                Log::error("WhatsApp falhou para parcelamento {$this->parcelamentoId}: {$e->getMessage()}");
            }
        }

        Log::info("Parcela gerada: parcelamento {$this->parcelamentoId}, parcela {$numeroParcela}/{$parcelamento->total_parcelas}, Asaas ID: {$cobranca['pagamento_id']}");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Job de parcela falhou para parcelamento {$this->parcelamentoId}: {$exception->getMessage()}");
    }
}
