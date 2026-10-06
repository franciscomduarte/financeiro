<?php

declare(strict_types=1);

return [
    /*
    | Tabelas vinculadas a uma clínica (coluna tenant_id). Usado pela validação
    | (exists/unique por clínica). Todo Model dessas tabelas usa o trait BelongsToClinica.
    */
    'tabelas' => [
        'agendamentos', 'atendimento_fichas', 'atendimento_injetaveis', 'atendimentos', 'bloqueios_agenda', 'cobrancas', 'comissao_fechamentos', 'conta_consumo_faturas', 'contas_consumo',
        'contrato_pagamentos', 'contratos', 'contratos_reajustes', 'documento_categorias', 'documento_versoes',
        'documentos', 'fichas_modelos', 'fornecedores', 'notas_fiscais', 'notificacao_configuracoes', 'notificacoes', 'grade_horarios', 'obrigacao_fiscal_lancamentos', 'obrigacoes_fiscais', 'orcamento_itens', 'orcamentos',
        'pacote_sessoes', 'pacotes', 'paciente_acessos', 'pacientes', 'parcelamentos', 'procedimentos', 'profissionais', 'prontuario_evolucoes', 'prontuario_fotos', 'prontuario_modelos',
        'pesquisas_satisfacao', 'plano_tratamento_itens', 'planos_tratamento', 'prontuario_orientacoes', 'prontuario_termos', 'recorrencias', 'relacionamento_contatos', 'stock_batches', 'stock_categories',
        'stock_movements', 'stock_products', 'taxas_cartao', 'transacao_anexos', 'transacoes',
    ],

    // Fuso para exibir carimbos de data/hora (created_at etc. são gravados em UTC)
    'fuso_horario' => 'America/Sao_Paulo',

    // Dias de teste grátis no autocadastro (fase 3)
    'dias_teste' => 14,
];
