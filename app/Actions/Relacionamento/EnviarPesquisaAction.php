<?php

declare(strict_types=1);

namespace App\Actions\Relacionamento;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\PesquisaSatisfacao;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Cria a pesquisa de satisfação do atendimento e manda o link pelo WhatsApp do paciente. */
class EnviarPesquisaAction
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    public function execute(string $agendamentoId): PesquisaSatisfacao
    {
        $this->clinicaAtual->garantirEscrita();

        return DB::transaction(function () use ($agendamentoId): PesquisaSatisfacao {
            $agendamento = Agendamento::query()->with('paciente:id,nome,telefone,anonimizado_em')
                ->select(['id', 'paciente_id', 'profissional_id', 'status'])->lockForUpdate()->findOrFail($agendamentoId);

            if ($agendamento->status !== StatusAgendamento::Realizado) {
                throw new RuntimeException('A pesquisa vai depois que o atendimento é realizado.');
            }
            if (PesquisaSatisfacao::query()->where('agendamento_id', $agendamento->id)->exists()) {
                throw new RuntimeException('A pesquisa deste atendimento já foi enviada.');
            }
            $telefone = $agendamento->paciente?->telefone;
            if (! $telefone || $agendamento->paciente->anonimizado_em) {
                throw new RuntimeException('Cadastre o telefone do paciente para enviar a pesquisa.');
            }

            $pesquisa = PesquisaSatisfacao::create([
                'paciente_id'     => $agendamento->paciente_id,
                'agendamento_id'  => $agendamento->id,
                'profissional_id' => $agendamento->profissional_id,
                'token'           => Str::random(48),
                'user_id'         => auth()->id(),
                'enviada_em'      => now(),
            ]);

            $nome     = explode(' ', trim($agendamento->paciente->nome))[0];
            $mensagem = "Olá, {$nome}! Obrigado pela visita à {$this->clinicaAtual->nome()}. 💕\n"
                . "Pode nos contar como foi? Leva menos de 1 minuto:\n{$pesquisa->link()}";

            if (! $this->whatsApp->enviarTextoParaTelefone($telefone, $mensagem)) {
                throw new RuntimeException('O WhatsApp não respondeu. Confira a conexão nas configurações da clínica e tente de novo.');
            }

            return $pesquisa;
        });
    }
}
