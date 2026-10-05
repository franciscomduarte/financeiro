<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Comissão fechada de um profissional num mês, já lançada como despesa. */
class ComissaoFechamento extends Model
{
    use BelongsToClinica;

    protected $table = 'comissao_fechamentos';

    protected $fillable = ['profissional_id', 'competencia', 'base', 'percentual', 'valor', 'transacao_id', 'user_id'];

    protected $casts = [
        'base'       => 'decimal:2',
        'percentual' => 'decimal:2',
        'valor'      => 'decimal:2',
    ];

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }
}
