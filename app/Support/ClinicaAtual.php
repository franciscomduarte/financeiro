<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusClinica;
use App\Models\Clinica;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Context;

/**
 * Clínica ativa da requisição/job (registrada como singleton no Service Container).
 * Toda consulta aos Models da clínica é filtrada por ela (ver BelongsToClinica).
 */
class ClinicaAtual
{
    private ?Clinica $clinica = null;

    /** Uma gravação foi barrada pelo modo somente leitura (lido pelas telas Livewire para avisar). */
    private bool $escritaBloqueada = false;

    /** Dono da plataforma vendo a clínica para dar suporte (sempre somente leitura). */
    private bool $suporte = false;

    /** Usuário da requisição tem o perfil "Somente consulta" (não muda ao trocar de clínica em executarComo). */
    private bool $leituraPorPerfil = false;

    public function definir(?Clinica $clinica): void
    {
        $this->clinica = $clinica;
        $this->suporte = false;

        // Logs estruturados e payload dos jobs passam a carregar a clínica
        $clinica
            ? Context::add('tenant_id', $clinica->id)
            : Context::forget('tenant_id');
    }

    public function get(): ?Clinica
    {
        return $this->clinica;
    }

    public function id(): ?string
    {
        return $this->clinica?->id;
    }

    /** Pasta de arquivos da clínica ativa: "clinicas/{id}/{subpasta}". */
    public function pasta(string $subpasta): string
    {
        $id = $this->id() ?? throw new \App\Exceptions\ClinicaNaoDefinidaException('arquivos');

        return 'clinicas/' . $id . '/' . ltrim($subpasta, '/');
    }

    /** Nome para textos (e-mails, mensagens); sem clínica, o nome da plataforma. */
    public function nome(): string
    {
        return $this->clinica?->nome ?? (string) config('app.name');
    }

    /** Ativa a clínica em modo suporte (super admin, somente leitura). */
    public function definirSuporte(Clinica $clinica): void
    {
        $this->definir($clinica);
        $this->suporte = true;
    }

    public function emSuporte(): bool
    {
        return $this->suporte && $this->clinica !== null;
    }

    /** Marca a requisição como do perfil "Somente consulta" (DefinirClinicaAtual). */
    public function definirLeituraPorPerfil(bool $somenteLeitura): void
    {
        $this->leituraPorPerfil = $somenteLeitura;
    }

    public function leituraPorPerfil(): bool
    {
        return $this->leituraPorPerfil;
    }

    public function somenteLeitura(): bool
    {
        return $this->emSuporte() || $this->leituraPorPerfil || (bool) $this->clinica?->somenteLeitura();
    }

    public function mensagemSomenteLeitura(): string
    {
        return match (true) {
            $this->emSuporte()      => \App\Exceptions\ClinicaSomenteLeituraException::SUPORTE,
            $this->leituraPorPerfil => \App\Exceptions\ClinicaSomenteLeituraException::PERFIL,
            default                 => \App\Exceptions\ClinicaSomenteLeituraException::TESTE_ENCERRADO,
        };
    }

    /** Barra gravações em modo somente leitura (teste encerrado, suporte ou perfil somente consulta). */
    public function garantirEscrita(): void
    {
        if (! $this->somenteLeitura()) {
            return;
        }

        // Marca para a tela Livewire trocar a mensagem genérica de erro pelo aviso (AppServiceProvider)
        $this->escritaBloqueada = true;

        throw new \App\Exceptions\ClinicaSomenteLeituraException($this->mensagemSomenteLeitura());
    }

    /** Informa (e zera) se alguma gravação foi barrada desde a última consulta. */
    public function consumirEscritaBloqueada(): bool
    {
        $bloqueada              = $this->escritaBloqueada;
        $this->escritaBloqueada = false;

        return $bloqueada;
    }

    public function definida(): bool
    {
        return $this->clinica !== null;
    }

    /**
     * Executa $callback com $clinica ativa e restaura a anterior em seguida.
     *
     * @template T
     * @param  callable(Clinica): T  $callback
     * @return T
     */
    public function executarComo(Clinica $clinica, callable $callback): mixed
    {
        $anterior = $this->clinica;
        $this->definir($clinica);

        try {
            $resultado = $callback($clinica);

            // Job::dispatch() só enfileira quando o PendingDispatch é destruído; força isso aqui,
            // ainda com a clínica ativa (senão o job sairia com a clínica anterior).
            if ($resultado instanceof PendingDispatch) {
                unset($resultado);
                return null;
            }

            return $resultado;
        } finally {
            $this->definir($anterior);
        }
    }

    /**
     * Executa $callback para cada clínica em uso (tarefas agendadas): ignora bloqueadas
     * e as que estão em modo somente leitura (teste encerrado).
     *
     * @param  callable(Clinica): void  $callback
     */
    public function paraCadaClinica(callable $callback): void
    {
        Clinica::query()
            ->where('status', '!=', StatusClinica::Bloqueada->value)
            ->where(fn ($q) => $q->where('status', '!=', StatusClinica::Teste->value)
                ->orWhereNull('teste_ate')
                ->orWhere('teste_ate', '>=', today()->toDateString()))
            ->lazyById(100)
            ->each(fn (Clinica $clinica) => $this->executarComo($clinica, $callback));
    }
}
