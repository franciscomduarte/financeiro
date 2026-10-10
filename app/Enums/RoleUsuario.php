<?php

declare(strict_types=1);

namespace App\Enums;

/** Perfil do usuário em cada clínica (pivot clinica_user.papel). */
enum RoleUsuario: string
{
    case Admin        = 'admin';
    case Recepcao     = 'recepcao';
    case Profissional = 'profissional';
    case Financeiro   = 'financeiro';
    case Consulta     = 'consulta';

    public function label(): string
    {
        return match ($this) {
            self::Admin        => 'Administrador',
            self::Recepcao     => 'Recepção',
            self::Profissional => 'Profissional',
            self::Financeiro   => 'Financeiro',
            self::Consulta     => 'Somente consulta',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Admin        => 'Acesso a tudo, inclusive usuários e dados da clínica.',
            self::Recepcao     => 'Agenda, cadastro de pacientes e cobranças. Sem prontuário e sem financeiro.',
            self::Profissional => 'Só a própria agenda, os próprios pacientes (com prontuário) e o estoque.',
            self::Financeiro   => 'Financeiro, cobranças, estoque e administrativo. Sem dados clínicos.',
            self::Consulta     => 'Só vê a agenda, os pacientes e os leads. Não cria, não altera e não exclui nada.',
        };
    }

    /** @return array<int, Modulo> */
    public function modulos(): array
    {
        return match ($this) {
            self::Admin        => Modulo::cases(),
            self::Recepcao     => [Modulo::Agenda, Modulo::Pacientes, Modulo::Cobrancas, Modulo::Notificacoes, Modulo::Leads],
            self::Profissional => [Modulo::Agenda, Modulo::Pacientes, Modulo::DadosClinicos, Modulo::Estoque],
            self::Consulta     => [Modulo::Agenda, Modulo::Pacientes, Modulo::Leads],
            self::Financeiro   => [
                Modulo::Inicio, Modulo::Cobrancas, Modulo::Lancamentos, Modulo::Relatorios, Modulo::Taxas,
                Modulo::Estoque, Modulo::Administrativo,
            ],
        };
    }

    /** Perfil que só consulta: nenhuma gravação (ver DefinirClinicaAtual e ClinicaAtual::somenteLeitura). */
    public function somenteLeitura(): bool
    {
        return $this === self::Consulta;
    }

    public function pode(Modulo $modulo): bool
    {
        return in_array($modulo, $this->modulos(), true);
    }

    /** Rota da primeira tela depois do login. */
    public function paginaInicial(): string
    {
        return $this->pode(Modulo::Inicio) ? 'dashboard' : 'agenda.index';
    }
}
