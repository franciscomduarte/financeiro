<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Enums\TipoCampoFicha;
use App\Models\FichaModelo;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria ou edita um modelo de ficha. Atendimentos já feitos guardam a própria cópia dos campos,
 * então mudar o modelo não altera o que já foi registrado.
 */
class SalvarFichaModeloAction
{
    public const MAX_CAMPOS = 60;

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** @param  array{nome: string, descricao?: ?string, ativo: bool, campos: array<int, array{id?: ?string, tipo: string, rotulo: string, opcoes?: array<int, string>|string}>}  $dados */
    public function execute(?string $id, array $dados): FichaModelo
    {
        $this->clinicaAtual->garantirEscrita();

        $nome = trim($dados['nome']);
        if ($nome === '') {
            throw new InvalidArgumentException('Dê um nome para a ficha.');
        }

        $campos = [];
        $ids    = [];
        foreach (array_values($dados['campos']) as $i => $c) {
            $tipo   = TipoCampoFicha::tryFrom((string) ($c['tipo'] ?? '')) ?? throw new InvalidArgumentException('Tipo de pergunta inválido.');
            $rotulo = mb_substr(trim((string) ($c['rotulo'] ?? '')), 0, 150);
            if ($rotulo === '') {
                throw new InvalidArgumentException('A pergunta ' . ($i + 1) . ' está sem texto.');
            }

            $opcoes = is_array($c['opcoes'] ?? null) ? $c['opcoes'] : preg_split('/\r\n|\n/', (string) ($c['opcoes'] ?? ''));
            $opcoes = $tipo->temOpcoes()
                ? array_values(array_unique(array_filter(array_map(fn ($o) => mb_substr(trim((string) $o), 0, 100), $opcoes), fn ($o) => $o !== '')))
                : [];
            if ($tipo->temOpcoes() && count($opcoes) < 2) {
                throw new InvalidArgumentException("Coloque pelo menos 2 opções em \"{$rotulo}\" (uma por linha).");
            }

            // Mantém o id da pergunta para as respostas antigas continuarem casando
            $idCampo = preg_match('/^[a-z0-9]{6,20}$/', (string) ($c['id'] ?? '')) && ! in_array($c['id'], $ids, true) ? (string) $c['id'] : Str::lower(Str::random(10));
            $ids[]   = $idCampo;

            $campos[] = ['id' => $idCampo, 'tipo' => $tipo->value, 'rotulo' => $rotulo, 'opcoes' => array_slice($opcoes, 0, 40)];
        }

        if ($campos === []) {
            throw new InvalidArgumentException('Adicione pelo menos uma pergunta.');
        }
        if (count($campos) > self::MAX_CAMPOS) {
            throw new InvalidArgumentException('Uma ficha pode ter até ' . self::MAX_CAMPOS . ' perguntas.');
        }

        $modelo = $id !== null ? FichaModelo::query()->findOrFail($id) : new FichaModelo(['ordem' => (int) FichaModelo::query()->max('ordem') + 1]);
        $modelo->fill([
            'nome'      => mb_substr($nome, 0, 100),
            'descricao' => filled($dados['descricao'] ?? null) ? mb_substr(trim((string) $dados['descricao']), 0, 255) : null,
            'ativo'     => (bool) $dados['ativo'],
            'campos'    => $campos,
        ])->save();

        Log::info('[Atendimento] modelo de ficha salvo', ['modelo_id' => $modelo->id, 'user_id' => auth()->id()]);

        return $modelo;
    }

    /** Sobe ou desce a ficha na lista do atendimento. */
    public function mover(string $id, int $direcao): void
    {
        $this->clinicaAtual->garantirEscrita();

        $lista = FichaModelo::query()->select(['id', 'ordem'])->orderBy('ordem')->orderBy('nome')->get()->values();
        $pos   = $lista->search(fn ($m) => $m->id === $id);
        $alvo  = $pos === false ? null : $pos + ($direcao < 0 ? -1 : 1);
        if ($alvo === null || $alvo < 0 || $alvo >= $lista->count()) {
            return;
        }

        $ordem = $lista->pluck('id')->all();
        [$ordem[$pos], $ordem[$alvo]] = [$ordem[$alvo], $ordem[$pos]];
        foreach ($ordem as $i => $modeloId) {
            FichaModelo::query()->whereKey($modeloId)->update(['ordem' => $i]);
        }
    }
}
