<?php

declare(strict_types=1);

namespace App\Enums;

/** Áreas do sistema liberadas por perfil (ver RoleUsuario::modulos()). */
enum Modulo: string
{
    case Inicio             = 'inicio';             // painel com números financeiros
    case Agenda             = 'agenda';
    case Notificacoes       = 'notificacoes';       // histórico e agendadas (WhatsApp/e-mail)
    case Pacientes          = 'pacientes';
    case DadosClinicos      = 'dados_clinicos';     // anamnese, prontuário, fotos
    case Cobrancas          = 'cobrancas';
    case Lancamentos        = 'lancamentos';        // lançamentos e recorrências
    case Relatorios         = 'relatorios';
    case Taxas              = 'taxas';
    case Estoque            = 'estoque';
    case Administrativo     = 'administrativo';     // fornecedores, contratos, contas, obrigações, documentos
    case ConfiguracaoAgenda = 'configuracao_agenda';// profissionais, horários, procedimentos, bloqueios
    case DadosClinica       = 'dados_clinica';
    case Usuarios           = 'usuarios';
}
