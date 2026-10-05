<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ImportarPacientesCsvAction;
use App\Models\Clinica;
use App\Support\ClinicaAtual;
use Illuminate\Console\Command;

/** Importa pacientes de um CSV de outro sistema. O arquivo fica só no servidor (dados pessoais fora do Git). */
class ImportarPacientesCommand extends Command
{
    protected $signature = 'pacientes:importar
        {arquivo : Caminho do CSV no servidor}
        {--clinica= : Slug ou id da clínica que recebe os pacientes}
        {--simular : Mostra o que seria feito, sem gravar nada}';

    protected $description = 'Importa pacientes de uma planilha CSV (exportada de outro sistema) para uma clínica';

    public function handle(ImportarPacientesCsvAction $importar, ClinicaAtual $clinicaAtual): int
    {
        $chave = (string) $this->option('clinica');
        $clinica = $chave === '' ? null : Clinica::query()->where('slug', $chave)
            ->orWhere(fn ($q) => preg_match('/^[0-9a-f-]{36}$/i', $chave) ? $q->where('id', $chave) : $q->whereRaw('false'))
            ->first();

        if ($clinica === null) {
            $this->error('Informe a clínica com --clinica=slug. Clínicas: ' . Clinica::query()->orderBy('nome')->limit(50)->pluck('slug')->implode(', '));

            return self::FAILURE;
        }

        $simular = (bool) $this->option('simular');
        $this->info(($simular ? '[SIMULAÇÃO] ' : '') . "Importando para {$clinica->nome}...");

        try {
            $resumo = $clinicaAtual->executarComo($clinica, fn () => $importar->execute((string) $this->argument('arquivo'), $simular));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Resultado', 'Pacientes'], [
            ['Novos', $resumo['criados']],
            ['Já existiam, completados', $resumo['completados']],
            ['Já existiam, sem mudança', $resumo['iguais']],
            ['Ignorados', count($resumo['ignorados'])],
        ]);
        foreach ($resumo['ignorados'] as $motivo) {
            $this->warn($motivo);
        }

        $this->line($simular
            ? 'Nada foi gravado. Rode de novo sem --simular para importar.'
            : 'Pronto. Lembre de apagar o arquivo CSV do servidor.');

        return self::SUCCESS;
    }
}
