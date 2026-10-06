<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RegistroDoProntuario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PDF anexado ao prontuário (exame, laudo, receita). Arquivo no disco privado. */
class ProntuarioAnexo extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $table = 'prontuario_anexos';

    protected $fillable = ['paciente_id', 'atendimento_id', 'user_id', 'nome', 'arquivo_path', 'mime', 'tamanho'];

    protected $casts = ['tamanho' => 'integer'];

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class, 'atendimento_id');
    }

    /** "1,2 MB", "340 KB" */
    public function tamanhoTexto(): string
    {
        return $this->tamanho >= 1048576
            ? number_format($this->tamanho / 1048576, 1, ',', '.') . ' MB'
            : max(1, (int) round($this->tamanho / 1024)) . ' KB';
    }
}
