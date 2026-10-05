<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoVersao extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'documento_versoes';

    protected $fillable = [
        'documento_id',
        'numero_documento',
        'data_emissao',
        'data_validade',
        'arquivo_path',
        'arquivo_nome',
        'observacoes',
    ];

    protected $casts = [
        'data_emissao'  => 'date',
        'data_validade' => 'date',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function temArquivo(): bool
    {
        return $this->arquivo_path !== null;
    }
}
