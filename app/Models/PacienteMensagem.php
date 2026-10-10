<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mensagem de WhatsApp trocada com um paciente (recebida ou enviada pela clínica). Não é editada. */
class PacienteMensagem extends Model
{
    use BelongsToClinica, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'paciente_mensagens';

    protected $fillable = ['paciente_id', 'user_id', 'enviada', 'texto', 'mensagem_id', 'lida_em'];

    protected $casts = ['enviada' => 'boolean', 'lida_em' => 'datetime'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
