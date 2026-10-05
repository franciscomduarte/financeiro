<?php

declare(strict_types=1);

namespace App\Actions\Pacotes;

use App\Enums\StatusPacote;
use App\Models\Agendamento;
use App\Models\Pacote;
use App\Models\PacoteSessao;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Desconta uma sessão do pacote pelo atendimento realizado. Chamar dentro da transação da conclusão. */
class UsarSessaoPacoteAction
{
    public function execute(string $pacoteId, Agendamento $agendamento): PacoteSessao
    {
        return DB::transaction(function () use ($pacoteId, $agendamento): PacoteSessao {
            $pacote = Pacote::query()->lockForUpdate()->findOrFail($pacoteId);

            if ($pacote->paciente_id !== $agendamento->paciente_id) {
                throw new RuntimeException('Esse pacote é de outro paciente.');
            }
            if ($pacote->status !== StatusPacote::Ativo || $pacote->saldo() < 1) {
                throw new RuntimeException('Esse pacote não tem sessões disponíveis.');
            }
            if ($pacote->vencido()) {
                throw new RuntimeException('Esse pacote venceu em ' . $pacote->validade->format('d/m/Y') . '.');
            }
            if (PacoteSessao::query()->where('agendamento_id', $agendamento->id)->exists()) {
                throw new RuntimeException('Este atendimento já usou uma sessão de pacote.');
            }

            $sessao = PacoteSessao::create([
                'pacote_id'       => $pacote->id,
                'agendamento_id'  => $agendamento->id,
                'profissional_id' => $agendamento->profissional_id,
                'usada_em'        => $agendamento->inicio_em->toDateString(),
            ]);

            $pacote->sessoes_usadas++;
            if ($pacote->saldo() === 0) {
                $pacote->status = StatusPacote::Concluido;
            }
            $pacote->save();

            return $sessao;
        });
    }
}
