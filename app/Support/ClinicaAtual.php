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

    public function definir(?Clinica $clinica): void
    {
        $this->clinica = $clinica;

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
     * Executa $callback para cada clínica não bloqueada (tarefas agendadas).
     *
     * @param  callable(Clinica): void  $callback
     */
    public function paraCadaClinica(callable $callback): void
    {
        Clinica::query()
            ->where('status', '!=', StatusClinica::Bloqueada->value)
            ->lazyById(100)
            ->each(fn (Clinica $clinica) => $this->executarComo($clinica, $callback));
    }
}
