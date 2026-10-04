<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

class UpdateTransacaoAction
{
    public function __construct(
        private readonly CalcularValoresTransacaoAction $calcularValores,
    ) {}

    public function execute(Transacao $transacao, array $data): Transacao
    {
        return DB::transaction(function () use ($transacao, $data): Transacao {
            $tipo           = TipoTransacao::from(
                $data['tipo'] ?? $transacao->getRawOriginal('tipo')
            );

            $formaPagamento = FormaPagamento::from(
                $data['forma_pagamento'] ?? $transacao->getRawOriginal('forma_pagamento')
            );

            $valorBruto = isset($data['valor_bruto'])
                ? (float) $data['valor_bruto']
                : (float) $transacao->valor_bruto;

            $recalcular = isset($data['valor_bruto'])
                || isset($data['forma_pagamento'])
                || isset($data['tipo']);

            if ($recalcular) {
                $valores = $this->calcularValores->execute(
                    valorBruto:      $valorBruto,
                    formaPagamento:  $formaPagamento,
                    tipo:            $tipo,
                );
                $data['taxa_operacional'] = $valores['taxa_operacional'];
                $data['imposto_estimado'] = $valores['imposto_estimado'];
                $data['valor_liquido']    = $valores['valor_liquido'];
            }

            if (isset($data['forma_pagamento']) && $formaPagamento->parcelas() > 1) {
                $data['num_parcelas'] = $formaPagamento->parcelas();
            }

            // Pago sempre tem data de pagamento (o Dashboard soma por ela)
            $status        = StatusTransacao::from($data['status'] ?? $transacao->getRawOriginal('status'));
            $dataPagamento = array_key_exists('data_pagamento', $data) ? $data['data_pagamento'] : $transacao->data_pagamento;
            if ($status === StatusTransacao::Pago && empty($dataPagamento)) {
                $data['data_pagamento'] = now()->toDateString();
            }

            $transacao->update($data);

            return $transacao->fresh();
        });
    }
}
