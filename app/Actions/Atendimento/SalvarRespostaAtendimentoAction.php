<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Enums\TipoCampoFicha;
use App\Models\AtendimentoFicha;
use App\Models\FichaModelo;
use App\Support\HtmlSeguro;
use InvalidArgumentException;

/**
 * Salva uma resposta da ficha enquanto o profissional digita (salvamento automático).
 * A ficha do atendimento nasce na primeira resposta, com a cópia dos campos do modelo.
 */
class SalvarRespostaAtendimentoAction
{
    use AtendimentoEditavel;

    public function execute(string $atendimentoId, string $modeloId, string $campoId, mixed $valor): mixed
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId);

        $ficha = AtendimentoFicha::query()->where('atendimento_id', $atendimento->id)->where('modelo_id', $modeloId)->first();
        if ($ficha === null) {
            $modelo = FichaModelo::query()->select(['id', 'nome', 'campos'])->findOrFail($modeloId);
            $ficha  = AtendimentoFicha::query()->createOrFirst(
                ['atendimento_id' => $atendimento->id, 'modelo_id' => $modelo->id],
                ['titulo' => $modelo->nome, 'campos' => $modelo->campos, 'respostas' => (object) []],
            );
        }

        $campo = collect($ficha->campos)->firstWhere('id', $campoId)
            ?? throw new InvalidArgumentException('Pergunta não encontrada nesta ficha.');
        $limpo = self::normalizar(TipoCampoFicha::from($campo['tipo']), $campo['opcoes'] ?? [], $valor);

        // jsonb_set por chave: dois salvamentos simultâneos não apagam um ao outro
        AtendimentoFicha::query()->whereKey($ficha->id)->update(["respostas->{$campoId}" => $limpo]);

        return $limpo;
    }

    /** @param  array<int, string>  $opcoes */
    public static function normalizar(TipoCampoFicha $tipo, array $opcoes, mixed $valor): mixed
    {
        return match ($tipo) {
            TipoCampoFicha::TextoRico => HtmlSeguro::limpar(is_string($valor) ? $valor : ''),
            TipoCampoFicha::Texto     => mb_substr(trim(is_scalar($valor) ? (string) $valor : ''), 0, 1000),
            TipoCampoFicha::Escolha   => in_array($valor, $opcoes, true) ? $valor : '',
            TipoCampoFicha::Multipla  => array_values(array_intersect($opcoes, is_array($valor) ? $valor : [])),
            TipoCampoFicha::SimNao    => in_array($valor, ['sim', 'nao'], true) ? $valor : '',
            TipoCampoFicha::Numero    => self::numero($valor),
            TipoCampoFicha::Data      => is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)
                && checkdate((int) substr($valor, 5, 2), (int) substr($valor, 8, 2), (int) substr($valor, 0, 4)) ? $valor : '',
            TipoCampoFicha::Titulo    => '',
        };
    }

    private static function numero(mixed $valor): string
    {
        $texto = str_replace(',', '.', trim(is_scalar($valor) ? (string) $valor : ''));
        if ($texto === '' || ! is_numeric($texto) || abs((float) $texto) >= 10_000_000) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $texto, 3, '.', ''), '0'), '.');
    }
}
