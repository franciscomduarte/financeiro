<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StatusFornecedor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornecedor extends Model
{
    use BelongsToClinica, HasFactory, HasUuids;

    protected $table = 'fornecedores';

    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'servico_prestado',
        'categoria',
        'contato_nome',
        'contato_telefone',
        'contato_email',
        'contato_emergencia_nome',
        'contato_emergencia_telefone',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'status' => StatusFornecedor::class,
    ];

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'fornecedor_id');
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class, 'fornecedor_id');
    }
}
