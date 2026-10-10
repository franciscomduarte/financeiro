<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\EtapaLead;
use App\Enums\TipoInteracaoLead;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/** Edita o lead, move no funil, registra contatos e converte em paciente. */
class AtualizarLeadAction
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** @param  array{nome: string, telefone?: ?string, email?: ?string, origem: string, procedimento_id?: int|string|null, interesse?: ?string, observacoes?: ?string, responsavel_id?: int|string|null}  $dados */
    public function salvar(string $id, array $dados): Lead
    {
        $this->clinicaAtual->garantirEscrita();
        $lead = Lead::query()->findOrFail($id);

        $chave = Telefone::chave($dados['telefone'] ?? null);
        $email = filled($dados['email'] ?? null) ? mb_strtolower(trim((string) $dados['email'])) : null;
        if ($chave === null && $email === null) {
            throw new InvalidArgumentException('Informe um telefone com DDD ou um e-mail.');
        }

        $lead->update([
            'nome'            => mb_substr(trim($dados['nome']), 0, 150),
            'telefone'        => Telefone::formatar($dados['telefone'] ?? null),
            'telefone_chave'  => $chave,
            'email'           => $email,
            'origem'          => $dados['origem'],
            'procedimento_id' => filled($dados['procedimento_id'] ?? null) && Procedimento::query()->whereKey((int) $dados['procedimento_id'])->exists() ? (int) $dados['procedimento_id'] : null,
            'interesse'       => filled($dados['interesse'] ?? null) ? mb_substr(trim((string) $dados['interesse']), 0, 255) : null,
            'observacoes'     => filled($dados['observacoes'] ?? null) ? mb_substr(trim((string) $dados['observacoes']), 0, 5000) : null,
            'responsavel_id'  => filled($dados['responsavel_id'] ?? null) ? (int) $dados['responsavel_id'] : null,
        ]);

        return $lead;
    }

    public function moverEtapa(string $id, EtapaLead $etapa, ?string $motivoPerda = null): Lead
    {
        $this->clinicaAtual->garantirEscrita();

        if ($etapa === EtapaLead::Fechado) {
            throw new RuntimeException('Para fechar, use "Converter em paciente": o cadastro do paciente é criado junto.');
        }
        if ($etapa === EtapaLead::JaPaciente) {
            throw new RuntimeException('Para marcar como "Já é paciente", escolha a ficha do paciente.');
        }
        $motivoPerda = filled($motivoPerda) ? mb_substr(trim($motivoPerda), 0, 150) : null;
        if ($etapa === EtapaLead::Perdido && $motivoPerda === null) {
            throw new InvalidArgumentException('Escolha o motivo da perda.');
        }

        return DB::transaction(function () use ($id, $etapa, $motivoPerda): Lead {
            $lead = Lead::query()->lockForUpdate()->findOrFail($id);
            if ($lead->etapa === $etapa) {
                return $lead;
            }
            if ($lead->etapa === EtapaLead::Fechado) {
                throw new RuntimeException('Este lead já virou paciente.');
            }

            $de = $lead->etapa;
            $lead->update([
                'etapa'               => $etapa,
                'paciente_id'         => $de === EtapaLead::JaPaciente ? null : $lead->paciente_id, // voltou ao funil: desfaz o vínculo
                'motivo_perda'        => $etapa === EtapaLead::Perdido ? $motivoPerda : null,
                'proximo_contato_em'  => $etapa === EtapaLead::Perdido ? null : $lead->proximo_contato_em,
                'ultima_interacao_em' => now(),
            ]);
            $this->registrarNoHistorico($lead, TipoInteracaoLead::Etapa, "{$de->label()} → {$etapa->label()}" . ($motivoPerda ? " ({$motivoPerda})" : ''));

            return $lead;
        });
    }

    /** Ligação, WhatsApp enviado ou anotação; opcionalmente marca o próximo contato. */
    public function registrarContato(string $id, \App\Enums\TipoInteracaoLead $tipo, ?string $texto, ?string $proximoContato): Lead
    {
        $this->clinicaAtual->garantirEscrita();
        if (! in_array($tipo, [TipoInteracaoLead::Nota, TipoInteracaoLead::Ligacao, TipoInteracaoLead::WhatsAppEnviado], true)) {
            throw new InvalidArgumentException('Tipo de contato inválido.');
        }

        return DB::transaction(function () use ($id, $tipo, $texto, $proximoContato): Lead {
            $lead = Lead::query()->lockForUpdate()->findOrFail($id);
            $this->registrarNoHistorico($lead, $tipo, filled($texto) ? mb_substr(trim($texto), 0, 2000) : null);

            $dados = ['ultima_interacao_em' => now(), 'proximo_contato_em' => filled($proximoContato) ? $proximoContato : null];
            if ($tipo->contatoDaClinica()) {
                $dados['primeiro_contato_em'] = $lead->primeiro_contato_em ?? now();
                if ($lead->etapa === EtapaLead::Novo) {
                    $dados['etapa'] = EtapaLead::EmContato; // primeiro contato tira o lead de "Novo"
                }
            }
            $lead->update($dados);

            return $lead;
        });
    }

    /** Cria (ou reaproveita) o paciente e fecha o lead. */
    public function converter(string $id): Paciente
    {
        $this->clinicaAtual->garantirEscrita();

        return DB::transaction(function () use ($id): Paciente {
            $lead = Lead::query()->lockForUpdate()->findOrFail($id);
            if ($lead->paciente_id !== null && ($existente = Paciente::query()->find($lead->paciente_id))) {
                return $existente;
            }

            $paciente = BuscarPacienteDoLead::porContato($lead->telefone_chave, $lead->email)
                ?? Paciente::create([
                    'nome'     => $lead->nome,
                    'telefone' => $lead->telefone,
                    'email'    => $lead->email,
                    'origem'   => $lead->origem->label(),
                ]);

            $lead->update(['paciente_id' => $paciente->id, 'convertido_em' => now(), 'etapa' => EtapaLead::Fechado, 'proximo_contato_em' => null, 'ultima_interacao_em' => now()]);
            $this->registrarNoHistorico($lead, TipoInteracaoLead::Convertido, "Paciente: {$paciente->nome}");

            Log::info('[Leads] convertido em paciente', ['lead_id' => $lead->id, 'paciente_id' => $paciente->id, 'user_id' => auth()->id()]);

            return $paciente;
        });
    }

    /**
     * Quem entrou em contato já tinha ficha: liga o lead ao paciente e tira do funil (não conta como conversão).
     * Se a ficha não tem telefone/e-mail, guarda os do contato para as próximas mensagens serem reconhecidas.
     */
    public function vincularPaciente(string $id, string $pacienteId): Lead
    {
        $this->clinicaAtual->garantirEscrita();

        return DB::transaction(function () use ($id, $pacienteId): Lead {
            $lead = Lead::query()->lockForUpdate()->findOrFail($id);
            if ($lead->etapa === EtapaLead::Fechado) {
                throw new RuntimeException('Este lead já virou paciente.');
            }
            $paciente = Paciente::query()->select(['id', 'nome', 'telefone', 'email'])->whereNull('anonimizado_em')->find($pacienteId)
                ?? throw new InvalidArgumentException('Paciente não encontrado.');

            $completar = array_filter([
                'telefone' => blank($paciente->telefone) ? $lead->telefone : null,
                'email'    => blank($paciente->email) ? $lead->email : null,
            ]);
            if ($completar !== []) {
                $paciente->update($completar);
            }

            $de = $lead->etapa;
            $lead->update([
                'etapa' => EtapaLead::JaPaciente, 'paciente_id' => $paciente->id, 'motivo_perda' => null,
                'proximo_contato_em' => null, 'ultima_interacao_em' => now(),
            ]);
            $this->registrarNoHistorico($lead, TipoInteracaoLead::Etapa, "{$de->label()} → Já é paciente ({$paciente->nome})");

            Log::info('[Leads] ligado a paciente existente', ['lead_id' => $lead->id, 'paciente_id' => $paciente->id, 'user_id' => auth()->id()]);

            return $lead;
        });
    }

    private function registrarNoHistorico(Lead $lead, TipoInteracaoLead $tipo, ?string $texto): void
    {
        LeadInteracao::create(['lead_id' => $lead->id, 'user_id' => auth()->id(), 'tipo' => $tipo, 'texto' => $texto]);
    }
}
