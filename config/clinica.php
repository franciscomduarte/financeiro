<?php

declare(strict_types=1);

return [
    /*
    | Tabelas vinculadas a uma clínica (coluna tenant_id). Usado pela validação
    | (exists/unique por clínica). Todo Model dessas tabelas usa o trait BelongsToClinica.
    */
    'tabelas' => [
        'agendamentos', 'bloqueios_agenda', 'cobrancas', 'conta_consumo_faturas', 'contas_consumo',
        'contrato_pagamentos', 'contratos', 'contratos_reajustes', 'documento_categorias', 'documento_versoes',
        'documentos', 'fornecedores', 'grade_horarios', 'obrigacao_fiscal_lancamentos', 'obrigacoes_fiscais',
        'pacientes', 'parcelamentos', 'procedimentos', 'profissionais', 'stock_batches', 'stock_categories',
        'stock_movements', 'stock_products', 'taxas_cartao', 'transacao_anexos', 'transacoes',
    ],

    // Dias de teste grátis no autocadastro (fase 3)
    'dias_teste' => 14,
];
