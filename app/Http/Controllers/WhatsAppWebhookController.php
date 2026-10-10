<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notificacoes\AtualizarEntregaNotificacaoAction;
use App\Enums\StatusClinica;
use App\Enums\StatusNotificacao;
use App\Models\Clinica;
use App\Support\ClinicaAtual;
use App\Jobs\ProcessarAudioWhatsAppJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Recebe o webhook da Evolution API.
     * Sempre retorna 200 para evitar retentativas do bot.
     */
    public function handle(Request $request, ?string $evento = null): JsonResponse
    {
        // "Webhook by Events": o evento vem no endereço (messages-upsert) quando não vem no corpo
        if ($evento !== null && blank($request->input('event'))) {
            $request->merge(['event' => str_replace('-', '.', $evento)]);
        }

        // Validação do token do webhook (se configurado)
        if (! $this->tokenValido($request)) {
            Log::warning('WhatsApp webhook: token inválido', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized']);
        }

        // Retorno de entrega/leitura das mensagens enviadas pela clínica
        if (str_replace('_', '.', mb_strtolower((string) $request->input('event'))) === 'messages.update') {
            return response()->json(['status' => 'ok', 'atualizadas' => $this->atualizarEntregas($request)]);
        }

        $remoteJid = (string) $request->input('data.key.remoteJid', '');

        // Ignora grupos, canais e status
        if (str_ends_with($remoteJid, '@g.us') || str_ends_with($remoteJid, '@newsletter') || str_starts_with($remoteJid, 'status@')) {
            return response()->json(['status' => 'ignored_group']);
        }

        // Enviada pela clínica (celular, WhatsApp Web ou pelo sistema): entra na conversa do lead, se houver
        if ($request->boolean('data.key.fromMe')) {
            return response()->json(['status' => $this->registrarLead($request, $remoteJid, daClinica: true)]);
        }

        // Áudio do "WhatsApp da gestão" vira lançamento; o resto pode ser um lead novo
        [$telefoneRemetente] = self::contato($request, $remoteJid);
        $clinica = $telefoneRemetente !== null ? $this->clinicaDoRemetente($telefoneRemetente) : null;
        if ($request->input('data.messageType') !== 'audioMessage' || $clinica === null) {
            return response()->json(['status' => $clinica === null ? $this->registrarLead($request, $remoteJid) : 'ignored']);
        }

        $audioUrl = (string) $request->input('data.message.audioMessage.url', '');

        if (blank($audioUrl)) {
            Log::warning('WhatsApp webhook: URL do áudio ausente', ['payload' => $request->all()]);
            return response()->json(['status' => 'missing_audio_url']);
        }

        // Despacha processamento para a fila (resposta imediata ao webhook); o job herda a clínica
        app(ClinicaAtual::class)->executarComo($clinica, function () use ($audioUrl, $remoteJid): void {
            ProcessarAudioWhatsAppJob::dispatch($audioUrl, $remoteJid);
        });

        Log::info('WhatsApp webhook: áudio enfileirado', ['jid' => $remoteJid, 'tenant_id' => $clinica->id]);

        return response()->json(['status' => 'queued']);
    }

    /** Mensagem de número desconhecido para a instância de uma clínica: vira lead (ou atualiza o lead). Da clínica: resposta ao lead. */
    private function registrarLead(Request $request, string $remoteJid, bool $daClinica = false): string
    {
        $evento = str_replace('_', '.', mb_strtolower((string) $request->input('event')));
        $instancia = (string) $request->input('instance', '');
        if ($evento !== 'messages.upsert' || $instancia === '') {
            return $this->ignorar('ignored_event', $request, $remoteJid);
        }

        // Número do contato: no remoteJid ou, quando o WhatsApp usa o código interno (…@lid), nos campos alternativos
        [$telefone, $lid] = self::contato($request, $remoteJid);
        if ($telefone === null && $lid === null) {
            return $this->ignorar('ignored_jid', $request, $remoteJid);
        }

        $clinica = Clinica::query()->select(['id', 'nome', 'status', 'evolution_instance', 'whatsapp_numero'])
            ->where('evolution_instance', $instancia)->where('status', '!=', StatusClinica::Bloqueada->value)->first();
        if ($clinica === null) {
            Log::warning('WhatsApp webhook: nenhuma clínica com esta instância (confira "Instância" em Dados da clínica)', ['instancia' => $instancia]);

            return 'ignored_unknown_instance';
        }

        $mensagem = (array) $request->input('data.message', []);
        $tipo     = (string) $request->input('data.messageType', '');
        $texto    = \App\Support\MensagemWhatsApp::texto($mensagem, $tipo);
        if ($texto === null) {
            return $this->ignorar('ignored_type', $request, $remoteJid); // reação, mensagem apagada, aviso interno
        }
        if ($texto === '[mensagem]') {
            // Formato ainda não reconhecido: registra só a estrutura (sem conteúdo) para incluir depois
            Log::warning('WhatsApp webhook: tipo de mensagem não reconhecido', [
                'tenant_id' => $clinica->id, 'messageType' => $tipo, 'campos' => array_keys($mensagem),
            ]);
        }

        try {
            $resultado = app(ClinicaAtual::class)->executarComo($clinica, fn () => app(\App\Actions\Leads\RegistrarMensagemWhatsAppAction::class)
                ->execute($telefone, mb_substr(trim((string) $request->input('data.pushName', '')), 0, 150) ?: null, $texto, $lid,
                    (string) $request->input('data.key.id', '') ?: null, $daClinica));
            Log::info('WhatsApp webhook: mensagem recebida', ['tenant_id' => $clinica->id, 'resultado' => $resultado]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook: falha ao registrar lead', ['tenant_id' => $clinica->id, 'erro' => $e->getMessage()]);

            return 'error';
        }

        return (string) $resultado;
    }

    /** Evolution v1/v2: "data" é um objeto ou uma lista; status em texto (DELIVERY_ACK, READ) ou número. */
    private function atualizarEntregas(Request $request): int
    {
        $data  = $request->input('data', []);
        $itens = is_array($data) && array_is_list($data) ? $data : [$data];
        $acao  = app(AtualizarEntregaNotificacaoAction::class);
        $total = 0;

        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id     = $item['keyId'] ?? $item['key']['id'] ?? $item['id'] ?? null;
            $bruto  = $item['status'] ?? $item['update']['status'] ?? null;
            $status = match (true) {
                in_array($bruto, ['DELIVERY_ACK', 3, '3'], true)          => StatusNotificacao::Entregue,
                in_array($bruto, ['READ', 'PLAYED', 4, 5, '4', '5'], true) => StatusNotificacao::Lida,
                in_array($bruto, ['ERROR', 0, '0'], true)                 => StatusNotificacao::Falhou,
                default                                                   => null,
            };

            if (is_string($id) && $id !== '' && $status !== null && $acao->porIdExterno($id, $status, 'O WhatsApp informou erro na entrega.')) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Telefone (só dígitos) e/ou código LID do contato.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function contato(Request $request, string $remoteJid): array
    {
        $candidatos = [$remoteJid, (string) $request->input('data.key.senderPn', ''), (string) $request->input('data.key.remoteJidAlt', ''),
            (string) $request->input('data.key.participantPn', ''), (string) $request->input('data.senderPn', '')];

        $telefone = null;
        foreach ($candidatos as $jid) {
            if (str_ends_with($jid, '@s.whatsapp.net')) {
                $telefone = strtok($jid, '@') ?: null;
                break;
            }
        }
        $lid = str_ends_with($remoteJid, '@lid') ? (strtok($remoteJid, '@') ?: null) : null;

        return [$telefone, $lid];
    }

    /** Mensagem descartada: registra o motivo (sem o conteúdo) para diagnóstico. */
    private function ignorar(string $motivo, Request $request, string $remoteJid): string
    {
        Log::info('WhatsApp webhook: mensagem ignorada', [
            'motivo' => $motivo, 'evento' => (string) $request->input('event'), 'instancia' => (string) $request->input('instance'),
            'tipo_contato' => str_contains($remoteJid, '@') ? substr($remoteJid, strpos($remoteJid, '@')) : '(vazio)',
            'campos_key' => array_keys((array) $request->input('data.key', [])),
        ]);

        return $motivo;
    }

    private function tokenValido(Request $request): bool
    {
        $tokenEsperado = config('services.whatsapp.webhook_token');

        // Se nenhum token configurado, qualquer requisição é aceita
        if (blank($tokenEsperado)) {
            return true;
        }

        // Evolution API envia o token no header "apikey" (algumas versões, no corpo)
        $recebido = (string) ($request->header('apikey') ?? $request->input('apikey', ''));

        return $recebido !== '' && hash_equals((string) $tokenEsperado, $recebido);
    }

    /** Clínica cujo número autorizado (clinicas.whatsapp_numero) enviou a mensagem. */
    private function clinicaDoRemetente(string $remoteJid): ?Clinica
    {
        $remetente = preg_replace('/\D/', '', $remoteJid);
        if ($remetente === '') {
            return null;
        }

        return Clinica::query()
            ->whereNotNull('whatsapp_numero')
            ->where('status', '!=', StatusClinica::Bloqueada->value)
            ->limit(500)
            ->get(['id', 'nome', 'status', 'whatsapp_numero'])
            ->first(function (Clinica $c) use ($remetente): bool {
                $autorizado = preg_replace('/\D/', '', (string) $c->whatsapp_numero);

                return $autorizado !== '' && str_contains($remetente, $autorizado);
            });
    }
}
