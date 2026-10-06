<?php

declare(strict_types=1);

namespace App\Actions\Relacionamento;

use App\Enums\TipoContatoRelacionamento;
use App\Models\Paciente;
use App\Models\RelacionamentoContato;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Contato de relacionamento (retorno, aniversário, paciente sumido): envia pelo WhatsApp, só para
 * quem aceitou mensagens (LGPD), ou só registra que a recepção já falou com o paciente.
 */
class RegistrarContatoAction
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    /** @param  array{procedimento?: ?string}  $contexto */
    public function execute(TipoContatoRelacionamento $tipo, string $pacienteId, string $referencia, bool $enviarWhatsapp, array $contexto = []): RelacionamentoContato
    {
        $this->clinicaAtual->garantirEscrita();

        $paciente = Paciente::query()->select(['id', 'nome', 'telefone', 'aceita_whatsapp_marketing'])->findOrFail($pacienteId);

        if (RelacionamentoContato::query()->where(['paciente_id' => $paciente->id, 'tipo' => $tipo, 'referencia' => $referencia])->exists()) {
            throw new RuntimeException('Este contato já foi feito.');
        }

        if ($enviarWhatsapp) {
            if (! $paciente->aceita_whatsapp_marketing) {
                throw new RuntimeException('Este paciente não autorizou mensagens por WhatsApp. Fale com ele de outro jeito e marque como feito.');
            }
            if (! $paciente->telefone) {
                throw new RuntimeException('Cadastre o telefone do paciente para enviar por WhatsApp.');
            }
            $texto = self::mensagem($tipo, $paciente->nome, $this->clinicaAtual->nome(), $contexto);
            if (! $this->whatsApp->enviarTextoParaTelefone($paciente->telefone, $texto)) {
                throw new RuntimeException('O WhatsApp não respondeu. Confira a conexão nas configurações da clínica e tente de novo.');
            }
            app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::from($tipo->value), \App\Enums\CanalNotificacao::WhatsApp, $paciente, (string) $paciente->telefone,
                \App\Enums\TipoNotificacao::from($tipo->value)->label(), $texto, true, idExterno: $this->whatsApp->ultimoIdMensagem);
        }

        $contato = RelacionamentoContato::create([
            'paciente_id' => $paciente->id,
            'tipo'        => $tipo,
            'referencia'  => $referencia,
            'canal'       => $enviarWhatsapp ? 'whatsapp' : 'manual',
            'user_id'     => auth()->id(),
        ]);

        Log::info('[Relacionamento] contato registrado', [
            'tipo' => $tipo->value, 'paciente_id' => $paciente->id, 'canal' => $contato->canal, 'user_id' => auth()->id(),
        ]);

        return $contato;
    }

    /** @param  array{procedimento?: ?string}  $contexto */
    public static function mensagem(TipoContatoRelacionamento $tipo, string $nomeCompleto, string $clinica, array $contexto = []): string
    {
        $nome = explode(' ', trim($nomeCompleto))[0];

        return match ($tipo) {
            TipoContatoRelacionamento::Retorno => "Olá, {$nome}! Já está chegando a hora do seu retorno"
                . (! empty($contexto['procedimento']) ? " de {$contexto['procedimento']}" : '')
                . " aqui na {$clinica}. Vamos agendar? É só responder esta mensagem com o melhor dia e horário. 😊",
            TipoContatoRelacionamento::Aniversario => "Feliz aniversário, {$nome}! 🎉 Toda a equipe da {$clinica} deseja um novo ano cheio de saúde, alegria e autoestima. Um abraço!",
            TipoContatoRelacionamento::Sumido => "Olá, {$nome}! Sentimos sua falta aqui na {$clinica}. 💕 Que tal reservar um horário para cuidar de você? É só responder esta mensagem.",
        };
    }
}
