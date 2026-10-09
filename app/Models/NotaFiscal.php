<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PadraoNfse;
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
        'tomador_nome', 'tomador_cpf', 'tomador_email', 'tomador_endereco', 'data_competencia', 'ultima_consulta_em', 'numero', 'codigo_verificacao', 'url', 'url_xml',
        'mensagem_erro', 'consultas', 'autorizada_em', 'cancelada_em', 'justificativa_cancelamento', 'padrao',
    ];

    protected $casts = [
        'status'        => StatusNotaFiscal::class,
        'padrao'        => PadraoNfse::class,
        'homologacao'   => 'boolean',
        'valor'         => 'decimal:2',
        'consultas'     => 'integer',
        'autorizada_em' => 'datetime',
        'cancelada_em'  => 'datetime',
        'tomador_endereco'   => 'array',
        'data_competencia'   => 'date',
        'ultima_consulta_em' => 'datetime',
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
