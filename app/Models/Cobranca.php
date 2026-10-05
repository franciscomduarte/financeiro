<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobranca extends Model
{
    use BelongsToClinica;

    protected $table = 'cobrancas';

    protected $fillable = [
        'paciente_id',
        'parcelamento_id',
        'numero_parcela',
        'asaas_id',
        'valor',
        'vencimento',
        'mes_referencia',
        'status',
        'qr_code_texto',
        'link_fatura',
        'whatsapp_enviado_em',
        'email_enviado_em',
        'pago_em',
    ];

    protected $casts = [
        'vencimento'          => 'date',
        'whatsapp_enviado_em' => 'datetime',
        'email_enviado_em'    => 'datetime',
        'pago_em'             => 'datetime',
        'valor'               => 'decimal:2',
        'numero_parcela'      => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function parcelamento(): BelongsTo
    {
        return $this->belongsTo(Parcelamento::class, 'parcelamento_id');
    }

    public function isPago(): bool
    {
        return $this->status === 'RECEIVED';
    }

    public function isVencido(): bool
    {
        return $this->status === 'OVERDUE';
    }
}
