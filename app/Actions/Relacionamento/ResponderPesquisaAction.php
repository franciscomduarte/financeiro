<?php

declare(strict_types=1);

namespace App\Actions\Relacionamento;

use App\Models\PesquisaSatisfacao;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Grava a resposta do paciente (página pública, sem login). Grava direto na tabela para valer
 * mesmo quando a clínica está em modo somente leitura: a resposta é do paciente, não da clínica.
 */
class ResponderPesquisaAction
{
    public function execute(PesquisaSatisfacao $pesquisa, int $nota, ?string $comentario): void
    {
        if ($nota < 0 || $nota > 10) {
            throw new RuntimeException('Escolha uma nota de 0 a 10.');
        }

        $atualizadas = DB::table('pesquisas_satisfacao')
            ->where('id', $pesquisa->id)
            ->whereNull('respondida_em')
            ->update([
                'nota'          => $nota,
                'comentario'    => $comentario !== null && trim($comentario) !== '' ? mb_substr(trim($comentario), 0, 2000) : null,
                'respondida_em' => now(),
                'updated_at'    => now(),
            ]);

        if ($atualizadas === 0) {
            throw new RuntimeException('Esta pesquisa já foi respondida. Obrigado!');
        }
    }
}
