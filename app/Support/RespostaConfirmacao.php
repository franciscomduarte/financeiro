<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/** Lê a resposta do paciente ao pedido de confirmação: true = confirma, false = não vem/remarcar, null = outro assunto. */
final class RespostaConfirmacao
{
    private const NAO = '/^n$|\b(nao|remarcar|remarca|desmarcar|desmarca|cancelar|cancela|cancelo|outro dia|outro horario|imprevisto)\b/';

    private const SIM = '/^(sim|s|ss|confirmo|confirmado|confirmada|confirmar|confirmadissimo|confirmadissima|ok|okay|okk|blz|beleza|certo|certeza|com certeza|claro|combinado|pode confirmar|estarei ai|estarei la|vou sim|vou|estarei|positivo|isso|perfeito)\b/';

    public static function interpretar(string $texto): ?bool
    {
        $original = trim($texto);
        if ($original === '' || mb_strlen($original) > 300) {
            return null;
        }
        if (preg_match('/^[\s👍👌✅✔️🙏😊🙂☺️💜❤️🥰]+$/u', $original)) {
            $positivo = str_contains($original, '👍') || str_contains($original, '👌') || str_contains($original, '✅') || str_contains($original, '✔');

            return $positivo ? true : null;
        }

        $normalizado = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9 ]+/', ' ', Str::lower(Str::ascii($original)))));
        if ($normalizado === '') {
            return null;
        }
        if (preg_match(self::NAO, $normalizado)) {
            return false;
        }

        return preg_match(self::SIM, $normalizado) ? true : null;
    }
}
