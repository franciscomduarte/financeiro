<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Grupos do plano de contas, na ordem da DRE gerencial de uma clínica.
 * Aportes, investimentos e retiradas mexem no caixa mas ficam fora do resultado.
 */
enum GrupoPlanoContas: string
{
    // Entradas
    case ReceitaServicos     = 'receita_servicos';
    case ReceitaProdutos     = 'receita_produtos';
    case OutrasReceitas      = 'outras_receitas';
    case ReceitasFinanceiras = 'receitas_financeiras';
    case Aportes             = 'aportes';
    // Saídas
    case Deducoes            = 'deducoes';
    case CustosVariaveis     = 'custos_variaveis';
    case Pessoal             = 'pessoal';
    case Ocupacao            = 'ocupacao';
    case Administrativas     = 'administrativas';
    case Marketing           = 'marketing';
    case DespesasFinanceiras = 'despesas_financeiras';
    case Investimentos       = 'investimentos';
    case Retiradas           = 'retiradas';

    public function label(): string
    {
        return match ($this) {
            self::ReceitaServicos     => 'Receita de serviços',
            self::ReceitaProdutos     => 'Venda de produtos',
            self::OutrasReceitas      => 'Outras receitas',
            self::ReceitasFinanceiras => 'Receitas financeiras',
            self::Aportes             => 'Aportes e empréstimos recebidos',
            self::Deducoes            => 'Impostos sobre a receita',
            self::CustosVariaveis     => 'Custos variáveis',
            self::Pessoal             => 'Pessoal',
            self::Ocupacao            => 'Ocupação (aluguel, contas de consumo)',
            self::Administrativas     => 'Despesas administrativas',
            self::Marketing           => 'Marketing',
            self::DespesasFinanceiras => 'Despesas financeiras',
            self::Investimentos       => 'Investimentos (equipamentos, reforma)',
            self::Retiradas           => 'Retiradas dos sócios e empréstimos pagos',
        };
    }

    public function tipo(): TipoTransacao
    {
        return in_array($this, [self::ReceitaServicos, self::ReceitaProdutos, self::OutrasReceitas, self::ReceitasFinanceiras, self::Aportes], true)
            ? TipoTransacao::Entrada
            : TipoTransacao::Saida;
    }

    /** Despesas fixas (estrutura) na DRE. */
    public function fixa(): bool
    {
        return in_array($this, [self::Pessoal, self::Ocupacao, self::Administrativas, self::Marketing], true);
    }

    /** Fora do resultado: só afeta o caixa. */
    public function foraDoResultado(): bool
    {
        return in_array($this, [self::Aportes, self::Investimentos, self::Retiradas], true);
    }

    /** @return array<int, self> */
    public static function doTipo(TipoTransacao $tipo): array
    {
        return array_values(array_filter(self::cases(), fn (self $g) => $g->tipo() === $tipo));
    }
}
