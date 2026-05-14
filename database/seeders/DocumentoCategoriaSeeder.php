<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentoCategoria;
use Illuminate\Database\Seeder;

class DocumentoCategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nome'             => 'Corpo de Bombeiros',
                'descricao'        => 'AVCB, CERCON e laudos de vistoria do corpo de bombeiros',
                'cor'              => 'red',
                'requer_validade'  => true,
                'alerta_dias_antes' => 60,
            ],
            [
                'nome'             => 'Vigilância Sanitária',
                'descricao'        => 'Alvará sanitário e certificados da vigilância sanitária',
                'cor'              => 'blue',
                'requer_validade'  => true,
                'alerta_dias_antes' => 60,
            ],
            [
                'nome'             => 'Certificados Profissionais',
                'descricao'        => 'Certificados e habilitações dos profissionais da equipe',
                'cor'              => 'indigo',
                'requer_validade'  => true,
                'alerta_dias_antes' => 30,
            ],
            [
                'nome'             => 'Controle de Pragas',
                'descricao'        => 'Certificados de dedetização, desratização e controle de vetores',
                'cor'              => 'green',
                'requer_validade'  => true,
                'alerta_dias_antes' => 30,
            ],
            [
                'nome'             => 'Empresa / CNPJ',
                'descricao'        => 'Contrato social, CNPJ, alvará de funcionamento e documentos societários',
                'cor'              => 'purple',
                'requer_validade'  => false,
                'alerta_dias_antes' => 30,
            ],
            [
                'nome'             => 'Segurança do Trabalho',
                'descricao'        => 'PPRA, PCMSO, laudos de segurança do trabalho',
                'cor'              => 'orange',
                'requer_validade'  => true,
                'alerta_dias_antes' => 45,
            ],
            [
                'nome'             => 'Relatórios e Laudos',
                'descricao'        => 'Relatório descritivo de atividades e outros laudos técnicos',
                'cor'              => 'amber',
                'requer_validade'  => false,
                'alerta_dias_antes' => 30,
            ],
            [
                'nome'             => 'Outros',
                'descricao'        => 'Documentos não categorizados',
                'cor'              => 'slate',
                'requer_validade'  => false,
                'alerta_dias_antes' => 30,
            ],
        ];

        foreach ($categorias as $dados) {
            DocumentoCategoria::firstOrCreate(['nome' => $dados['nome']], $dados);
        }
    }
}
