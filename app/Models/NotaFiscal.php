<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusNotaFiscal;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** NFS-e emitida pela Focus NFe a partir de uma receita. */
class NotaFiscal extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'transacao_id', 'paciente_id', 'user_id', 'referencia', 'status', 'homologacao', 'valor', 'discriminacao',
        'tomador_nome', 'tomador_cpf', 'tomador_email', 'numero', 'codigo_verificacao', 'url', 'url_xml',
        'mensagem_erro', 'consultas', 'autorizada_em', 'cancelada_em', 'justificativa_cancelamento',
    ];

    protected $casts = [
        'status'        => StatusNotaFiscal::class,
        'homologacao'   => 'boolean',
        'valor'         => 'decimal:2',
        'consultas'     => 'integer',
        'autorizada_em' => 'datetime',
        'cancelada_em'  => 'datetime',
    ];

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
