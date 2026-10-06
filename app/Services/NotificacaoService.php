<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CanalNotificacao;
use App\Enums\StatusNotificacao;
use App\Enums\TipoNotificacao;
use App\Jobs\EnviarNotificacaoJob;
use App\Models\Agendamento;
use App\Models\Notificacao;
use App\Models\NotificacaoConfiguracao;
use App\Models\Paciente;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Central de notificações ao paciente: cria (agora ou agendada), registra o que outras telas
 * enviaram e cancela lembretes que perderam o sentido. O envio em si fica no EnviarNotificacaoJob.
 */
class NotificacaoService
{
    /** @var array<string, NotificacaoConfiguracao> */
    private array $configuracoes = [];

    /** Configuração da clínica para o tipo; sem registro, valem os padrões (os dois canais ligados). */
    public function configuracao(TipoNotificacao $tipo): NotificacaoConfiguracao
    {
        return $this->configuracoes[app(\App\Support\ClinicaAtual::class)->id() . ':' . $tipo->value] ??= NotificacaoConfiguracao::query()
            ->select(['id', 'tenant_id', 'tipo', 'whatsapp', 'email', 'antecedencia_minutos', 'assunto', 'texto'])
            ->where('tipo', $tipo)->first()
            ?? new NotificacaoConfiguracao(['tipo' => $tipo, 'whatsapp' => true, 'email' => true]);
    }

    public function esquecerConfiguracoes(): void
    {
        $this->configuracoes = [];
    }

    /** Aviso imediato do agendamento (confirmado, remarcado, cancelado) nos canais ligados. */
    public function avisarAgendamento(Agendamento $agendamento, TipoNotificacao $tipo): void
    {
        $this->carregar($agendamento);
        foreach ($this->canais($agendamento, $tipo) as $canal) {
            $n = $this->criar($agendamento, $tipo, $canal, StatusNotificacao::Enviando, null);
            EnviarNotificacaoJob::dispatch($n->id)->onQueue('default')->afterCommit();
        }
    }

    /** Agenda os lembretes do horário (idempotente: não duplica os que já existem). */
    public function agendarLembretes(Agendamento $agendamento): int
    {
        if (! $agendamento->status->isPendente()) {
            return 0;
        }
        $this->carregar($agendamento);

        $criados = 0;
        foreach ([TipoNotificacao::LembreteVespera, TipoNotificacao::LembreteDia] as $tipo) {
            $quando = $agendamento->inicioReal()->subMinutes((int) $this->configuracao($tipo)->antecedenciaEfetiva());
            if ($quando->isPast()) {
                continue;
            }

            foreach ($this->canais($agendamento, $tipo) as $canal) {
                $existe = Notificacao::query()
                    ->where('origem_type', Agendamento::class)->where('origem_id', (string) $agendamento->id)
                    ->where('tipo', $tipo)->where('canal', $canal)
                    ->whereIn('status', [StatusNotificacao::Agendada, StatusNotificacao::Enviando, StatusNotificacao::Enviada, StatusNotificacao::Entregue, StatusNotificacao::Lida])
                    ->exists();
                if (! $existe) {
                    $this->criar($agendamento, $tipo, $canal, StatusNotificacao::Agendada, $quando);
                    $criados++;
                }
            }
        }

        return $criados;
    }

    /** Cancela os lembretes ainda não enviados do agendamento. */
    public function cancelarLembretes(Agendamento $agendamento, string $motivo): int
    {
        return Notificacao::query()
            ->where('origem_type', Agendamento::class)->where('origem_id', (string) $agendamento->id)
            ->where('status', StatusNotificacao::Agendada)
            ->update(['status' => StatusNotificacao::Cancelada, 'erro' => $motivo, 'updated_at' => now()]);
    }

    /**
     * Registra no histórico uma mensagem que outra tela já enviou (cobrança, orçamento, pesquisa...).
     * Nunca interrompe o envio: falha ao registrar só vai para o log.
     */
    public function registrar(
        TipoNotificacao $tipo,
        CanalNotificacao $canal,
        ?Paciente $paciente,
        string $destino,
        string $assunto,
        string $conteudo,
        bool $enviada,
        ?Model $origem = null,
        ?string $idExterno = null,
        ?string $erro = null,
        ?string $destinatarioNome = null,
    ): ?Notificacao {
        try {
            return Notificacao::create([
                'paciente_id'       => $paciente?->id,
                'tipo'              => $tipo,
                'canal'             => $canal,
                'status'            => $enviada ? StatusNotificacao::Enviada : StatusNotificacao::Falhou,
                'destinatario_nome' => mb_substr($destinatarioNome ?? (string) ($paciente?->nome ?? $destino), 0, 150),
                'destino'           => mb_substr($destino, 0, 150),
                'assunto'           => mb_substr($assunto, 0, 200),
                'conteudo'          => $conteudo,
                'origem_type'       => $origem ? $origem::class : null,
                'origem_id'         => $origem ? (string) $origem->getKey() : null,
                'enviada_em'        => $enviada ? now() : null,
                'id_externo'        => $idExterno,
                'erro'              => $enviada ? null : ($erro ?? 'Não foi possível enviar.'),
                'tentativas'        => 1,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Notificações] falha ao registrar envio', ['tipo' => $tipo->value, 'canal' => $canal->value, 'erro' => $e->getMessage()]);

            return null;
        }
    }

    /** Reenvia uma notificação (cria outra, com o mesmo texto, para manter o histórico). */
    public function reenviar(Notificacao $original): Notificacao
    {
        $nova = $original->replicate(['status', 'enviada_em', 'entregue_em', 'lida_em', 'id_externo', 'erro', 'tentativas', 'agendada_para']);
        $nova->status = StatusNotificacao::Enviando;
        $nova->save();

        EnviarNotificacaoJob::dispatch($nova->id)->onQueue('default')->afterCommit();

        return $nova;
    }

    private function carregar(Agendamento $agendamento): void
    {
        $agendamento->loadMissing(['paciente:id,nome,telefone,email', 'profissional:id,nome', 'procedimento:id,nome']);
    }

    /** @return array<int, CanalNotificacao> canais ligados para o tipo e com contato no cadastro */
    private function canais(Agendamento $agendamento, TipoNotificacao $tipo): array
    {
        $config   = $this->configuracao($tipo);
        $paciente = $agendamento->paciente;

        return array_values(array_filter([
            $config->whatsapp && filled($paciente?->telefone) ? CanalNotificacao::WhatsApp : null,
            $config->email && filled($paciente?->email) ? CanalNotificacao::Email : null,
        ]));
    }

    private function criar(Agendamento $agendamento, TipoNotificacao $tipo, CanalNotificacao $canal, StatusNotificacao $status, ?CarbonInterface $quando): Notificacao
    {
        $paciente = $agendamento->paciente;
        $assunto  = strtr($this->configuracao($tipo)->assuntoEfetivo(), app(\App\Actions\Notificacoes\MontarMensagemAgendamento::class)->variaveis($agendamento));

        return Notificacao::create([
            'paciente_id'       => $paciente?->id,
            'tipo'              => $tipo,
            'canal'             => $canal,
            'status'            => $status,
            'destinatario_nome' => mb_substr((string) $paciente?->nome, 0, 150),
            'destino'           => $canal === CanalNotificacao::WhatsApp ? $paciente?->telefone : $paciente?->email,
            'assunto'           => mb_substr($assunto, 0, 200),
            'origem_type'       => Agendamento::class,
            'origem_id'         => (string) $agendamento->id,
            'agendada_para'     => $quando,
        ]);
    }
}
