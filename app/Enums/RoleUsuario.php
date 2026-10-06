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

    public function label(): string
    {
        return match ($this) {
            self::Admin        => 'Administrador',
            self::Recepcao     => 'Recepção',
            self::Profissional => 'Profissional',
            self::Financeiro   => 'Financeiro',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Admin        => 'Acesso a tudo, inclusive usuários e dados da clínica.',
            self::Recepcao     => 'Agenda, cadastro de pacientes e cobranças. Sem prontuário e sem financeiro.',
            self::Profissional => 'Só a própria agenda, os próprios pacientes (com prontuário) e o estoque.',
            self::Financeiro   => 'Financeiro, cobranças, estoque e administrativo. Sem dados clínicos.',
        };
    }

    /** @return array<int, Modulo> */
    public function modulos(): array
    {
        return match ($this) {
            self::Admin        => Modulo::cases(),
            self::Recepcao     => [Modulo::Agenda, Modulo::Pacientes, Modulo::Cobrancas, Modulo::Notificacoes],
            self::Profissional => [Modulo::Agenda, Modulo::Pacientes, Modulo::DadosClinicos, Modulo::Estoque],
            self::Financeiro   => [
                Modulo::Inicio, Modulo::Cobrancas, Modulo::Lancamentos, Modulo::Relatorios, Modulo::Taxas,
                Modulo::Estoque, Modulo::Administrativo,
            ],
        };
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
