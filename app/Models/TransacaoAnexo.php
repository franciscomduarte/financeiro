<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoAnexo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransacaoAnexo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transacao_anexos';

    public $timestamps = false;

    protected $fillable = [
        'transacao_id',
        'tipo',
        'nome_arquivo',
        'caminho',
        'mime_type',
        'tamanho_bytes',
        'created_at',
    ];

    protected $casts = [
        'tipo'          => TipoAnexo::class,
        'tamanho_bytes' => 'integer',
        'created_at'    => 'datetime',
    ];

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }
}
