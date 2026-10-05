<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transacao extends Model
{
    use BelongsToClinica, HasFactory, HasUuids;

    protected $table = 'transacoes';

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
        'subcategoria',
        'centro_custo',
        'descricao',
        'cliente',
        'paciente_id',
        'agendamento_id',
        'fornecedor_id',
        'valor_bruto',
        'taxa_operacional',
        'imposto_estimado',
        'valor_liquido',
        'data_competencia',
        'data_pagamento',
        'forma_pagamento',
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
        'data_competencia'        => 'date',
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
}
