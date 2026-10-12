<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusCampanha;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Campanha de WhatsApp para pacientes escolhidos; o envio segue o ritmo de config('campanhas'). */
class Campanha extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'campanhas';

    protected $fillable = ['nome', 'mensagem', 'imagem_path', 'imagem_mime', 'status', 'total', 'enviados', 'falhas', 'iniciada_em', 'concluida_em', 'user_id'];

    protected $casts = [
        'status'       => StatusCampanha::class,
        'total'        => 'integer',
        'enviados'     => 'integer',
        'falhas'       => 'integer',
        'iniciada_em'  => 'datetime',
        'concluida_em' => 'datetime',
    ];

    public function destinatarios(): HasMany
    {
        return $this->hasMany(CampanhaDestinatario::class, 'campanha_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Texto para um paciente: troca {primeiro_nome} e {nome}. */
    public function mensagemPara(string $nomePaciente): string
    {
        $nome = trim($nomePaciente);

        return trim(strtr($this->mensagem, [
            '{primeiro_nome}' => (string) strtok($nome, ' '),
            '{nome}'          => $nome,
        ]));
    }
}
