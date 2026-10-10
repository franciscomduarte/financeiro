<?php

declare(strict_types=1);

namespace App\Actions\Pacientes;

use App\Models\PacienteMensagem;

/**
 * Guarda na ficha uma mensagem de WhatsApp trocada com um paciente (rodar com a clínica ativa).
 * Pelo id da Evolution, o eco do webhook não duplica o que foi enviado pelo sistema.
 */
class RegistrarMensagemPacienteAction
{
    public function execute(string $pacienteId, string $texto, bool $enviada, ?string $mensagemId = null, ?int $userId = null): PacienteMensagem
    {
        $dados = [
            'paciente_id' => $pacienteId,
            'enviada'     => $enviada,
            'texto'       => mb_substr(trim($texto), 0, 2000),
            'user_id'     => $userId,
            // Mensagem da clínica já nasce lida; a do paciente fica como nova até alguém abrir a ficha
            'lida_em'     => $enviada ? now() : null,
        ];

        if ($mensagemId === null) {
            return PacienteMensagem::create($dados);
        }

        $mensagem = PacienteMensagem::createOrFirst(['mensagem_id' => mb_substr($mensagemId, 0, 100)], $dados);
        if ($userId !== null && $mensagem->user_id === null) {
            $mensagem->update(['user_id' => $userId]); // o eco chegou antes: completa com quem enviou
        }

        return $mensagem;
    }
}
