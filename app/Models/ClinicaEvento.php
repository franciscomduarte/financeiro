<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AcaoClinicaEvento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Histórico da clínica visto pelo dono da plataforma (tabela global, sem escopo de clínica). */
class ClinicaEvento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'clinica_eventos';

    protected $fillable = ['clinica_id', 'user_id', 'acao', 'detalhes'];

    protected $casts = [
        'acao'     => AcaoClinicaEvento::class,
        'detalhes' => 'array',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Registra um evento da clínica, com quem fez (usuário logado) e detalhes opcionais. */
    public static function registrar(Clinica $clinica, AcaoClinicaEvento $acao, array $detalhes = []): self
    {
        return self::create([
            'clinica_id' => $clinica->id,
            'user_id'    => auth()->id(),
            'acao'       => $acao,
            'detalhes'   => $detalhes ?: null,
        ]);
    }
}
