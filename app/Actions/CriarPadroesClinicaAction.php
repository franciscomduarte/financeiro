<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Clinica;
use App\Models\ContaFinanceira;
use App\Models\DocumentoCategoria;
use App\Models\StockCategory;
use App\Models\TaxaCartao;
use App\Support\ClinicaAtual;

/**
 * Configurações iniciais de uma clínica nova: categorias de documentos e de estoque e as
 * modalidades de pagamento com taxa zerada (a clínica ajusta conforme a maquininha dela) e as contas
 * financeiras padrão (caixa, conta bancária e maquininha).
 * Nada de pacientes ou lançamentos.
 */
class CriarPadroesClinicaAction
{
    private const CATEGORIAS_DOCUMENTOS = [
        ['nome' => 'Corpo de Bombeiros', 'descricao' => 'AVCB, CERCON e laudos de vistoria do corpo de bombeiros', 'cor' => 'red', 'requer_validade' => true, 'alerta_dias_antes' => 60],
        ['nome' => 'Vigilância Sanitária', 'descricao' => 'Alvará sanitário e certificados da vigilância sanitária', 'cor' => 'blue', 'requer_validade' => true, 'alerta_dias_antes' => 60],
        ['nome' => 'Certificados Profissionais', 'descricao' => 'Certificados e habilitações dos profissionais da equipe', 'cor' => 'indigo', 'requer_validade' => true, 'alerta_dias_antes' => 30],
        ['nome' => 'Controle de Pragas', 'descricao' => 'Certificados de dedetização, desratização e controle de vetores', 'cor' => 'green', 'requer_validade' => true, 'alerta_dias_antes' => 30],
        ['nome' => 'Empresa / CNPJ', 'descricao' => 'Contrato social, CNPJ, alvará de funcionamento e documentos societários', 'cor' => 'purple', 'requer_validade' => false, 'alerta_dias_antes' => 30],
        ['nome' => 'Segurança do Trabalho', 'descricao' => 'PPRA, PCMSO, laudos de segurança do trabalho', 'cor' => 'orange', 'requer_validade' => true, 'alerta_dias_antes' => 45],
        ['nome' => 'Relatórios e Laudos', 'descricao' => 'Relatório descritivo de atividades e outros laudos técnicos', 'cor' => 'amber', 'requer_validade' => false, 'alerta_dias_antes' => 30],
        ['nome' => 'Outros', 'descricao' => 'Documentos não categorizados', 'cor' => 'slate', 'requer_validade' => false, 'alerta_dias_antes' => 30],
    ];

    private const CATEGORIAS_ESTOQUE = [
        ['name' => 'Toxina Botulínica', 'color' => '#7c3aed'],
        ['name' => 'Preenchedor', 'color' => '#db2777'],
        ['name' => 'Bioestimulador', 'color' => '#ea580c'],
        ['name' => 'Fio de PDO', 'color' => '#0891b2'],
        ['name' => 'Skinbooster', 'color' => '#059669'],
        ['name' => 'Cosmético Profissional', 'color' => '#d97706'],
        ['name' => 'Insumo de Procedimento', 'color' => '#64748b'],
        ['name' => 'Peeling & Ácidos', 'color' => '#0d9488'],
        ['name' => 'Anestésico', 'color' => '#dc2626'],
    ];

    private const MODALIDADES = [
        'pix', 'dinheiro', 'debito',
        'credito_1x', 'credito_2x', 'credito_3x', 'credito_4x', 'credito_5x', 'credito_6x',
        'credito_7x', 'credito_8x', 'credito_9x', 'credito_10x', 'credito_11x', 'credito_12x',
    ];

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function execute(Clinica $clinica): void
    {
        $this->clinicaAtual->executarComo($clinica, function (): void {
            foreach (self::CATEGORIAS_DOCUMENTOS as $categoria) {
                DocumentoCategoria::create($categoria);
            }

            foreach (self::CATEGORIAS_ESTOQUE as $categoria) {
                StockCategory::create($categoria);
            }

            foreach (self::MODALIDADES as $modalidade) {
                TaxaCartao::create(['modalidade' => $modalidade, 'percentual' => 0, 'ativo' => true]);
            }

            ContaFinanceira::garantirPadroes();
        });
    }
}
