<?php

declare(strict_types=1);

namespace App\Actions\Notificacoes;

use App\Enums\StatusNotificacao;
use App\Enums\TipoNotificacao;
use App\Models\Agendamento;
use App\Models\Notificacao;
use App\Models\NotificacaoConfiguracao;
use App\Services\NotificacaoService;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Salva canais, antecedência e texto de um aviso automático. Se mudar algo de um lembrete,
 * refaz os lembretes já agendados para valerem a nova regra.
 */
class SalvarConfiguracaoNotificacaoAction
{
    public function __construct(
        private readonly NotificacaoService $notificacoes,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    /** @param  array{whatsapp: bool, email: bool, antecedencia_minutos: ?int, assunto: ?string, texto: ?string}  $dados */
    public function execute(TipoNotificacao $tipo, array $dados): NotificacaoConfiguracao
    {
        $this->clinicaAtual->garantirEscrita();
        if (! $tipo->configuravel()) {
            throw new RuntimeException('Este tipo de notificação não tem configuração.');
        }

        $texto   = trim((string) $dados['texto']);
        $assunto = trim((string) $dados['assunto']);

        return DB::transaction(function () use ($tipo, $dados, $texto, $assunto): NotificacaoConfiguracao {
            $config = NotificacaoConfiguracao::query()->firstOrNew(['tipo' => $tipo]);
            $config->fill([
                'whatsapp'             => (bool) $dados['whatsapp'],
                'email'                => (bool) $dados['email'],
                'antecedencia_minutos' => $tipo->lembrete() ? $dados['antecedencia_minutos'] : null,
                // Igual ao padrão = guarda vazio, para acompanhar melhorias futuras do texto padrão
                'assunto'              => $assunto === '' || $assunto === $tipo->assuntoPadrao() ? null : $assunto,
                'texto'                => $texto === '' || $texto === $tipo->textoPadrao() ? null : $texto,
            ]);
            $mudouLembrete = $tipo->lembrete() && $config->isDirty(['whatsapp', 'email', 'antecedencia_minutos']);
            $config->save();

            $this->notificacoes->esquecerConfiguracoes();
            if ($mudouLembrete) {
                $this->refazerLembretes($tipo);
            }

            Log::info('[Notificações] configuração salva', ['tipo' => $tipo->value, 'user_id' => auth()->id()]);

            return $config;
        });
    }

    private function refazerLembretes(TipoNotificacao $tipo): void
    {
        Notificacao::query()->where('tipo', $tipo)->where('status', StatusNotificacao::Agendada)
            ->update(['status' => StatusNotificacao::Cancelada, 'erro' => 'Refeita com a nova configuração.', 'updated_at' => now()]);

        Agendamento::query()
            ->select(['id', 'tenant_id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em', 'status'])
            ->whereIn('status', ['agendado', 'confirmado'])
            ->where('inicio_em', '>', now())
            ->lazyById(200)
            ->each(fn (Agendamento $a) => $this->notificacoes->agendarLembretes($a));
    }
}
