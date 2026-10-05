<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatusPaciente;
use App\Exceptions\SimulacaoConcluida;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Importa pacientes de uma planilha CSV exportada de outro sistema (colunas "Nome", "Telefone",
 * "E-mail", "CPF", "Data de nascimento", "Ativo", "Sexo", "Estado civil", "Profissão", "Endereço",
 * "Origem"). Roda na clínica ativa. Quem já existe (mesmo CPF, ou mesmo nome e telefone) não é
 * duplicado: só recebe os campos que estavam vazios. Nada é sobrescrito.
 */
class ImportarPacientesCsvAction
{
    private const COLUNAS = [
        'nome'            => ['nome'],
        'tipo'            => ['tipo'],
        'telefone'        => ['telefone', 'celular'],
        'email'           => ['e-mail', 'email'],
        'ativo'           => ['ativo'],
        'data_nascimento' => ['data de nascimento', 'nascimento'],
        'sexo'            => ['sexo'],
        'estado_civil'    => ['estado civil'],
        'profissao'       => ['profissão', 'profissao'],
        'endereco'        => ['endereço', 'endereco'],
        'cpf'             => ['cpf'],
        'origem'          => ['origem'],
    ];

    /** Campos completados em quem já existe (só se estiverem vazios). */
    private const COMPLETAVEIS = ['cpf', 'data_nascimento', 'telefone', 'email', 'sexo', 'estado_civil', 'profissao', 'endereco', 'origem'];

    /**
     * @return array{criados: int, completados: int, iguais: int, ignorados: array<int, string>}
     */
    public function execute(string $caminho, bool $simular = false): array
    {
        $linhas = $this->ler($caminho);
        $resumo = ['criados' => 0, 'completados' => 0, 'iguais' => 0, 'ignorados' => []];

        try {
            DB::transaction(function () use ($linhas, $simular, &$resumo): void {
                foreach ($linhas as $numero => $linha) {
                    $dados = $this->normalizar($linha);

                    if ($dados['nome'] === null) {
                        $resumo['ignorados'][] = "Linha {$numero}: sem nome";
                        continue;
                    }
                    if ($dados['tipo'] !== null && mb_strtolower($dados['tipo']) !== 'paciente') {
                        $resumo['ignorados'][] = "Linha {$numero}: {$dados['nome']} não é paciente ({$dados['tipo']})";
                        continue;
                    }

                    $existente = $this->encontrar($dados);
                    if ($existente === null) {
                        Paciente::create([
                            ...array_intersect_key($dados, array_flip([...self::COMPLETAVEIS, 'nome'])),
                            'status' => $dados['ativo'] === false ? StatusPaciente::Inativo : StatusPaciente::Ativo,
                        ]);
                        $resumo['criados']++;
                        continue;
                    }

                    $faltando = array_filter(
                        array_intersect_key($dados, array_flip(self::COMPLETAVEIS)),
                        fn ($valor, $campo) => $valor !== null && blank($existente->getAttribute($campo)),
                        ARRAY_FILTER_USE_BOTH,
                    );
                    if ($faltando === []) {
                        $resumo['iguais']++;
                        continue;
                    }

                    $existente->fill($faltando)->save();
                    $resumo['completados']++;
                }

                if ($simular) {
                    throw new SimulacaoConcluida();
                }
            });
        } catch (SimulacaoConcluida) {
            // simulação: tudo desfeito, só o resumo vale
        }

        if (! $simular) {
            Log::info('[Pacientes] importação de CSV', [
                'criados' => $resumo['criados'], 'completados' => $resumo['completados'],
                'iguais' => $resumo['iguais'], 'ignorados' => count($resumo['ignorados']),
            ]);
        }

        return $resumo;
    }

    /** @return array<int, array<string, string>> linhas indexadas pelo número na planilha */
    private function ler(string $caminho): array
    {
        if (! is_readable($caminho)) {
            throw new RuntimeException("Não consegui ler o arquivo {$caminho}.");
        }

        $conteudo = (string) file_get_contents($caminho);
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo) ?? $conteudo; // BOM do Excel
        if (! mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $conteudo);
        rewind($handle);

        $primeira  = (string) strtok($conteudo, "\n");
        $separador = substr_count($primeira, ';') > substr_count($primeira, ',') ? ';' : ',';
        $cabecalho = array_map(fn ($c) => mb_strtolower(trim((string) $c)), fgetcsv($handle, null, $separador, '"', '') ?: []);

        $indices = [];
        foreach (self::COLUNAS as $campo => $nomes) {
            foreach ($nomes as $nome) {
                $i = array_search($nome, $cabecalho, true);
                if ($i !== false) {
                    $indices[$campo] = $i;
                    break;
                }
            }
        }
        if (! isset($indices['nome'])) {
            throw new RuntimeException('A planilha precisa ter a coluna "Nome".');
        }

        $linhas = [];
        $numero = 1;
        while (($valores = fgetcsv($handle, null, $separador, '"', '')) !== false) {
            $numero++;
            if ($valores === [null]) {
                continue; // linha em branco
            }
            $linhas[$numero] = array_map(fn ($i) => trim((string) ($valores[$i] ?? '')), $indices);
        }
        fclose($handle);

        return $linhas;
    }

    /** @param  array<string, string>  $l */
    private function normalizar(array $l): array
    {
        $vazio = fn (?string $v) => $v === null || trim($v) === '' ? null : trim($v);

        return [
            'nome'            => ($n = $vazio($l['nome'] ?? null)) ? $this->nomeProprio($n) : null,
            'tipo'            => $vazio($l['tipo'] ?? null),
            'telefone'        => $this->telefone($vazio($l['telefone'] ?? null)),
            'email'           => ($e = $vazio($l['email'] ?? null)) && filter_var($e, FILTER_VALIDATE_EMAIL) ? mb_strtolower($e) : null,
            'ativo'           => ($a = $vazio($l['ativo'] ?? null)) === null ? null : in_array(mb_strtolower($a), ['sim', 's', '1', 'ativo', 'true'], true),
            'data_nascimento' => $this->data($vazio($l['data_nascimento'] ?? null)),
            'sexo'            => $vazio($l['sexo'] ?? null),
            'estado_civil'    => $this->estadoCivil($vazio($l['estado_civil'] ?? null)),
            'profissao'       => ($p = $vazio($l['profissao'] ?? null)) ? mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1, 99) : null,
            'endereco'        => ($en = $vazio($l['endereco'] ?? null)) ? mb_substr(str_replace(', 00000-000', '', $en), 0, 255) : null,
            'cpf'             => $this->cpf($vazio($l['cpf'] ?? null)),
            'origem'          => ($o = $vazio($l['origem'] ?? null)) ? mb_substr($o, 0, 50) : null,
        ];
    }

    private function encontrar(array $dados): ?Paciente
    {
        if ($dados['cpf'] !== null && ($p = Paciente::query()->where('cpf', $dados['cpf'])->first())) {
            return $p;
        }
        if ($dados['telefone'] === null) {
            return Paciente::query()->whereRaw('lower(nome) = ?', [mb_strtolower($dados['nome'])])->whereNull('telefone')->first();
        }

        return Paciente::query()->whereRaw('lower(nome) = ?', [mb_strtolower($dados['nome'])])->where('telefone', $dados['telefone'])->first();
    }

    /** "Solteiro" → "Solteiro(a)", como nas opções da ficha. */
    private function estadoCivil(?string $v): ?string
    {
        return match (mb_strtolower((string) $v)) {
            ''                                       => null,
            'solteiro', 'solteira'                   => 'Solteiro(a)',
            'casado', 'casada'                       => 'Casado(a)',
            'divorciado', 'divorciada', 'separado', 'separada' => 'Divorciado(a)',
            'viuvo', 'viúvo', 'viuva', 'viúva'       => 'Viúvo(a)',
            'união estável', 'uniao estavel'         => 'União estável',
            default                                  => mb_substr((string) $v, 0, 30),
        };
    }

    /** "+55 (61) 99115-6221" → "(61) 99115-6221", como no cadastro da ficha. */
    private function telefone(?string $v): ?string
    {
        $d = preg_replace('/\D/', '', (string) $v) ?? '';
        if (strlen($d) > 11 && str_starts_with($d, '55')) {
            $d = substr($d, 2);
        }

        return match (strlen($d)) {
            11      => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
            10      => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
            0       => null,
            default => mb_substr((string) $v, 0, 20),
        };
    }

    private function data(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }

        try {
            $data = Carbon::createFromFormat('!d/m/Y', $v);
        } catch (Throwable) {
            return null;
        }

        // Recusa datas que não existem (31/02 viraria 03/03)
        return $data && in_array($v, [$data->format('d/m/Y'), $data->format('j/n/Y')], true) && $data->isPast() && $data->year > 1900 ? $data->toDateString() : null;
    }

    /** CPF só com 11 dígitos, no formato 000.000.000-00. */
    private function cpf(?string $v): ?string
    {
        $d = preg_replace('/\D/', '', (string) $v) ?? '';

        return strlen($d) === 11
            ? sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9))
            : null;
    }

    /** "MARIA FERNANDA DIAS" → "Maria Fernanda Dias"; nomes já escritos normalmente ficam como estão. */
    private function nomeProprio(string $nome): string
    {
        $nome = preg_replace('/\s+/', ' ', trim($nome)) ?? $nome;
        if ($nome !== mb_strtoupper($nome)) {
            return mb_substr($nome, 0, 150);
        }

        $palavras = array_map(
            fn ($p) => in_array(mb_strtolower($p), ['de', 'da', 'do', 'das', 'dos', 'e'], true) ? mb_strtolower($p) : mb_convert_case($p, MB_CASE_TITLE),
            explode(' ', $nome),
        );

        return mb_substr(implode(' ', $palavras), 0, 150);
    }
}

