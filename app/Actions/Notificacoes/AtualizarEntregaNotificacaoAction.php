<?php

declare(strict_types=1);

namespace App\Actions\Notificacoes;

use App\Enums\StatusNotificacao;
use App\Models\Clinica;
use App\Models\Notificacao;
use App\Models\Scopes\ClinicaScope;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\Log;

/**
 * Retorno de entrega vindo dos webhooks (WhatsApp/Brevo). Chega sem clínica ativa: localiza a
 * notificação sem o filtro de clínica e grava dentro da clínica dela. O status só avança.
 */
class AtualizarEntregaNotificacaoAction
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function porIdExterno(string $idExterno, StatusNotificacao $status, ?string $erro = null): bool
    {
        $n = Notificacao::query()->withoutGlobalScope(ClinicaScope::class)->where('id_externo', $idExterno)->latest()->first();

        return $n !== null && $this->aplicar($n, $status, $erro);
    }

    public function porId(string $id, StatusNotificacao $status, ?string $erro = null): bool
    {
        $n = Notificacao::query()->withoutGlobalScope(ClinicaScope::class)->find($id);

        return $n !== null && $this->aplicar($n, $status, $erro);
    }

    private function aplicar(Notificacao $n, StatusNotificacao $status, ?string $erro): bool
    {
        $atual = $n->status;

        $avanca = $status === StatusNotificacao::Falhou
            ? in_array($atual, [StatusNotificacao::Enviando, StatusNotificacao::Enviada], true) // não "desfaz" uma entrega
            : $atual->etapa() >= 0 && $status->etapa() > $atual->etapa();

        if (! $avanca) {
            return false;
        }

        $clinica = Clinica::query()->select(['id', 'nome', 'status'])->find($n->tenant_id);
        if ($clinica === null) {
            return false;
        }

        $this->clinicaAtual->executarComo($clinica, function () use ($n, $status, $erro): void {
            $n->status = $status;
            if ($status === StatusNotificacao::Entregue) {
                $n->entregue_em ??= now();
            }
            if ($status === StatusNotificacao::Lida) {
                $n->entregue_em ??= now();
                $n->lida_em ??= now();
            }
            if ($status === StatusNotificacao::Falhou) {
                $n->erro = $erro ?? 'Não foi entregue.';
            }
            $n->save();
        });

        Log::info('[Notificações] retorno de entrega', ['notificacao_id' => $n->id, 'tenant_id' => $n->tenant_id, 'status' => $status->value]);

        return true;
    }
}
