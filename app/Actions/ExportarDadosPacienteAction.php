<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AcaoAcessoPaciente;
use App\Models\Paciente;
use App\Services\RegistroAcessoPaciente;

/**
 * LGPD (direito de acesso/portabilidade): reúne os dados que a clínica guarda sobre o paciente
 * num documento JSON legível, para entregar quando ele pedir.
 */
class ExportarDadosPacienteAction
{
    public const LIMITE = 1000;

    public function __construct(
        private readonly RegistroAcessoPaciente $registro,
        private readonly ConsultarAcessosPacienteAction $consultarAcessos,
    ) {}

    /** @return array<string, mixed> */
    public function execute(Paciente $paciente): array
    {
        $paciente->load([
            'agendamentos' => fn ($q) => $q->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em', 'status', 'observacoes'])
                ->with(['profissional:id,nome', 'procedimento:id,nome'])->orderBy('inicio_em')->limit(self::LIMITE),
            'transacoes' => fn ($q) => $q->select(['id', 'paciente_id', 'descricao', 'valor_bruto', 'data_competencia', 'status', 'forma_pagamento'])
                ->orderBy('data_competencia')->limit(self::LIMITE),
            'cobrancas' => fn ($q) => $q->select(['id', 'paciente_id', 'valor', 'vencimento', 'mes_referencia', 'status', 'pago_em'])
                ->orderBy('vencimento')->limit(self::LIMITE),
        ]);

        $dados = [
            'gerado_em' => now()->toIso8601String(),
            'clinica'   => $paciente->clinica?->only(['nome', 'razao_social', 'cnpj', 'email_contato', 'telefone']),
            'paciente'  => [
                'nome'             => $paciente->nome,
                'cpf'              => $paciente->cpf,
                'data_nascimento'  => $paciente->data_nascimento?->toDateString(),
                'telefone'         => $paciente->telefone,
                'email'            => $paciente->email,
                'situacao'         => $paciente->status->value,
                'anamnese'         => $paciente->anamnese,
                'observacoes'      => $paciente->observacoes,
                'cadastrado_em'    => $paciente->created_at?->toIso8601String(),
            ],
            'consentimentos' => [
                'uso_dos_dados_em'       => $paciente->consentimento_em?->toIso8601String(),
                'mensagens_por_whatsapp' => $paciente->aceita_whatsapp_marketing,
                'mensagens_por_email'    => $paciente->aceita_email_marketing,
            ],
            'atendimentos' => $paciente->agendamentos->map(fn ($a) => [
                'data'         => $a->inicio_em?->toIso8601String(),
                'procedimento' => $a->procedimento?->nome,
                'profissional' => $a->profissional?->nome,
                'situacao'     => $a->status?->value,
                'observacoes'  => $a->observacoes,
            ])->all(),
            'pagamentos' => $paciente->transacoes->map(fn ($t) => [
                'data'      => $t->data_competencia?->toDateString(),
                'descricao' => $t->descricao,
                'valor'     => (float) $t->valor_bruto,
                'forma'     => $t->forma_pagamento?->value,
                'situacao'  => $t->status?->value,
            ])->all(),
            'cobrancas' => $paciente->cobrancas->map(fn ($c) => [
                'referencia' => $c->mes_referencia,
                'vencimento' => $c->vencimento?->toDateString(),
                'valor'      => (float) $c->valor,
                'situacao'   => $c->status,
                'pago_em'    => $c->pago_em?->toIso8601String(),
            ])->all(),
            'acessos_aos_dados' => $this->consultarAcessos->execute($paciente, self::LIMITE),
        ];

        $this->registro->registrar($paciente, AcaoAcessoPaciente::Exportou);

        return $dados;
    }
}
