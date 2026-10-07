<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAlertaVencimento: string
{
    case Despesa   = 'despesa';
    case Fatura    = 'fatura';
    case Guia      = 'guia';
    case Contrato  = 'contrato';
    case PagamentoContrato = 'pagamento_contrato';
    case Documento = 'documento';
    case Recebimento = 'recebimento';

    public function label(): string
    {
        return match ($this) {
            self::Despesa   => 'Despesa',
            self::Fatura    => 'Fatura de consumo',
            self::Guia      => 'Guia fiscal',
            self::Contrato  => 'Contrato',
            self::PagamentoContrato => 'Pagamento de contrato',
            self::Documento => 'Documento',
            self::Recebimento => 'A receber em atraso',
        };
    }

    /** Rota da tela onde o item é resolvido. */
    public function rota(): string
    {
        return match ($this) {
            self::Despesa   => 'financeiro.pagar',
            self::Recebimento => 'financeiro.receber',
            self::Fatura    => 'web.contas-consumo',
            self::Guia      => 'web.obrigacoes-fiscais',
            self::Contrato, self::PagamentoContrato => 'web.contratos',
            self::Documento => 'web.documentos',
        };
    }
}
