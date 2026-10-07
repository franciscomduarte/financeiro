<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Observers\TransacaoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(TransacaoObserver::class)]
class Transacao extends Model
{
    use BelongsToClinica, HasFactory, HasUuids;

    protected $table = 'transacoes';

    /** Escolha da venda no cartão (antecipar ou receber mês a mês); null usa o padrão da maquininha. Não é coluna. */
    public ?bool $anteciparCartao = null;

    /** Categorias de receita (entrada) disponíveis. */
    public const CATEGORIAS_ENTRADA = [
        'Procedimento Facial',
        'Depilação',
        'Massagem',
        'Skincare',
        'Produto Vendido',
        'Outros',
    ];

    /** Categorias de despesa (saída) disponíveis. */
    public const CATEGORIAS_SAIDA = [
        'Infraestrutura',
        'Utilidades',
        'Marketing',
        'Burocracia',
        'Reforma',
        'Impostos',
        'Pessoal',
        'Insumos',
        'Outros',
    ];

    protected $fillable = [
        'tipo',
        'fase',
        'categoria',
        'categoria_id',
        'subcategoria',
        'centro_custo',
        'descricao',
        'cliente',
        'paciente_id',
        'agendamento_id',
        'fornecedor_id',
        'contrato_id',
        'valor_bruto',
        'taxa_operacional',
        'imposto_estimado',
        'valor_liquido',
        'data_competencia',
        'data_vencimento',
        'data_pagamento',
        'valor_pago',
        'forma_pagamento',
        'conta_financeira_id',
        'num_parcelas',
        'parcela_atual',
        'status',
        'recorrencia',
        'data_inicio_recorrencia',
        'transacao_pai_id',
        'recorrencia_id',
        'observacoes',
    ];

    protected $casts = [
        'tipo'                    => TipoTransacao::class,
        'fase'                    => FaseTransacao::class,
        'status'                  => StatusTransacao::class,
        'recorrencia'             => RecorrenciaTransacao::class,
        'forma_pagamento'         => FormaPagamento::class,
        'valor_bruto'             => 'decimal:2',
        'taxa_operacional'        => 'decimal:2',
        'imposto_estimado'        => 'decimal:2',
        'valor_liquido'           => 'decimal:2',
        'valor_pago'              => 'decimal:2',
        'data_competencia'        => 'date',
        'data_vencimento'         => 'date',
        'data_pagamento'          => 'date',
        'data_inicio_recorrencia' => 'date',
        'num_parcelas'            => 'integer',
        'parcela_atual'           => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function transacaoPai(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_pai_id');
    }

    public function transacoesFilhas(): HasMany
    {
        return $this->hasMany(Transacao::class, 'transacao_pai_id');
    }

    /** Modelo de onde este lançamento foi gerado (lançamentos recorrentes). */
    public function recorrenciaModelo(): BelongsTo
    {
        return $this->belongsTo(Recorrencia::class, 'recorrencia_id');
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaFiscal::class, 'transacao_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(TransacaoAnexo::class, 'transacao_id');
    }

    /** Conta do plano de contas (grupo da DRE). */
    public function planoConta(): BelongsTo
    {
        return $this->belongsTo(PlanoConta::class, 'categoria_id');
    }

    /** Pagamentos/recebimentos (baixas) deste lançamento. */
    public function baixas(): HasMany
    {
        return $this->hasMany(TransacaoBaixa::class, 'transacao_id');
    }

    /** Conta prevista para pagar/receber (sugestão na hora da baixa). */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_financeira_id');
    }

    /** Quanto ainda falta pagar/receber. */
    public function valorAberto(): float
    {
        if ($this->status === StatusTransacao::Cancelado) {
            return 0.0;
        }

        return max(0.0, round((float) $this->valor_bruto - (float) $this->valor_pago, 2));
    }

    /** Dias de atraso (0 quando em dia ou já quitado). */
    public function diasAtraso(): int
    {
        if (! $this->status->emAberto() || $this->data_vencimento === null || ! $this->data_vencimento->lt(today())) {
            return 0;
        }

        return (int) $this->data_vencimento->diffInDays(today());
    }

    /**
     * Refaz valor pago, situação e data de pagamento a partir das baixas (sem disparar o observer).
     * Lançamento cancelado continua cancelado.
     */
    public function recalcularPagamento(): void
    {
        $baixas = $this->baixas()->get(['valor', 'data']);
        $pago   = round((float) $baixas->sum('valor'), 2);

        $this->valor_pago = $pago;
        if ($this->status !== StatusTransacao::Cancelado) {
            $quitado = $pago > 0 && $pago >= round((float) $this->valor_bruto, 2) - 0.004;
            $this->status = match (true) {
                $quitado  => StatusTransacao::Pago,
                $pago > 0 => StatusTransacao::Parcial,
                default   => StatusTransacao::Pendente,
            };
            $this->data_pagamento = $quitado ? $baixas->max('data') : null;
        }
        $this->saveQuietly();
        app(\App\Actions\Financeiro\SincronizarOrigemTituloAction::class)->execute($this);
    }
}
