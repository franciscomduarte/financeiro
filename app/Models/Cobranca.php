<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cobranca extends Model
{
    protected $table = 'cobrancas';

    protected $fillable = [
        'paciente_id',
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
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
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
