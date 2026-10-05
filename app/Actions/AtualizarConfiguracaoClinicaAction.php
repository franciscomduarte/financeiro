<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Clinica;
use App\Support\ClinicaAtual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Salva as configurações da clínica. Segredos (chaves) só são alterados quando um novo valor
 * é informado — campo em branco mantém o atual.
 */
class AtualizarConfiguracaoClinicaAction
{
    private const SEGREDOS = ['evolution_api_key', 'asaas_api_key', 'asaas_webhook_token'];

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function execute(Clinica $clinica, array $dados, ?UploadedFile $logo = null): Clinica
    {
        $this->clinicaAtual->garantirEscrita();

        foreach (self::SEGREDOS as $segredo) {
            if (! filled($dados[$segredo] ?? null)) {
                unset($dados[$segredo]);
            }
        }

        $logoAntigo = $clinica->logo_path;

        $clinica = DB::transaction(function () use ($clinica, $dados, $logo): Clinica {
            if ($logo) {
                $dados['logo_path'] = $logo->store($this->clinicaAtual->pasta('logo'), 'public');
            }

            $clinica->update($dados);

            return $clinica->fresh();
        });

        if ($logo && $logoAntigo) {
            Storage::disk('public')->delete($logoAntigo);
        }

        Log::info('[Clinica] configurações atualizadas', [
            'tenant_id' => $clinica->id,
            'user_id'   => auth()->id(),
            'campos'    => array_keys($dados), // nunca os valores (há segredos)
        ]);

        return $clinica;
    }

    /** Remove um segredo (ex.: desconectar o Asaas). */
    public function removerSegredo(Clinica $clinica, string $campo): void
    {
        abort_unless(in_array($campo, self::SEGREDOS, true), 422);
        $this->clinicaAtual->garantirEscrita();

        $clinica->update([$campo => null]);
        Log::info('[Clinica] segredo removido', ['tenant_id' => $clinica->id, 'user_id' => auth()->id(), 'campo' => $campo]);
    }
}
