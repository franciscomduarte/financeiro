<?php

declare(strict_types=1);

namespace App\Actions\Notificacoes;

use App\Models\Agendamento;
use App\Models\NotificacaoConfiguracao;
use App\Support\ClinicaAtual;
use Carbon\CarbonInterface;

/** Preenche as variáveis ({primeiro_nome}, {data}...) do texto configurado com os dados do agendamento. */
class MontarMensagemAgendamento
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** @return array{assunto: string, texto: string} */
    public function montar(Agendamento $agendamento, NotificacaoConfiguracao $config): array
    {
        $variaveis = $this->variaveis($agendamento);

        return [
            'assunto' => mb_substr(trim(strtr($config->assuntoEfetivo(), $variaveis)), 0, 200),
            'texto'   => trim(strtr($config->textoEfetivo(), $variaveis)),
        ];
    }

    /** @return array<string, string> */
    public function variaveis(Agendamento $agendamento): array
    {
        $fuso   = (string) config('clinica.fuso_horario');
        $inicio = $agendamento->inicioReal()->timezone($fuso);
        $nome   = trim((string) $agendamento->paciente?->nome);
        $motivo = trim((string) $agendamento->motivo_cancelamento);

        return [
            '{primeiro_nome}' => (string) strtok($nome, ' '),
            '{paciente}'      => $nome,
            '{clinica}'       => $this->clinicaAtual->nome(),
            '{procedimento}'  => (string) $agendamento->procedimento?->nome,
            '{profissional}'  => (string) $agendamento->profissional?->nome,
            '{data}'          => $inicio->format('d/m/Y'),
            '{dia_semana}'    => $inicio->locale('pt_BR')->translatedFormat('l'),
            '{hora}'          => $inicio->format('H:i'),
            '{quando}'        => $this->quando($inicio, now($fuso)),
            '{motivo}'        => $motivo !== '' ? "\n\n📝 Motivo: {$motivo}" : '',
        ];
    }

    private function quando(CarbonInterface $inicio, CarbonInterface $agora): string
    {
        return match (true) {
            $inicio->isSameDay($agora)                  => 'hoje',
            $inicio->isSameDay($agora->copy()->addDay()) => 'amanhã',
            default                                     => $inicio->locale('pt_BR')->translatedFormat('l') . ', ' . $inicio->format('d/m'),
        };
    }
}
