<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/** Clínica (tenant). Tabelas da clínica referenciam esta pelo campo tenant_id. */
class Clinica extends Model
{
    use HasUuids;

    protected $table = 'clinicas';

    protected $fillable = [
        'nome',
        'slug',
        'status',
        'teste_ate',
        'whatsapp_numero',
        'razao_social',
        'cnpj',
        'telefone',
        'email_contato',
        'endereco',
        'slogan',
        'logo_path',
        'aliquota_imposto',
        'evolution_instance',
        'evolution_api_key',
        'asaas_api_key',
        'asaas_sandbox',
        'asaas_webhook_token',
    ];

    /** Segredos nunca vão para arrays/JSON. */
    protected $hidden = ['evolution_api_key', 'asaas_api_key', 'asaas_webhook_token'];

    protected $casts = [
        'status'              => StatusClinica::class,
        'teste_ate'           => 'date',
        'aliquota_imposto'    => 'decimal:2',
        'asaas_sandbox'       => 'boolean',
        'evolution_api_key'   => 'encrypted',
        'asaas_api_key'       => 'encrypted',
        'asaas_webhook_token' => 'encrypted',
    ];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clinica_user')
            ->withPivot('papel')
            ->withTimestamps();
    }

    /** Alíquota como fração (6.00 → 0.06). */
    public function aliquotaImpostoFracao(): float
    {
        return (float) $this->aliquota_imposto / 100;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /** Assinatura curta para e-mails e mensagens: "Nome — slogan". */
    public function assinatura(): string
    {
        return $this->slogan ? "{$this->nome} — {$this->slogan}" : $this->nome;
    }

    public function whatsappConfigurado(): bool
    {
        return filled($this->evolution_instance) && filled($this->evolution_api_key);
    }

    public function asaasConfigurado(): bool
    {
        return filled($this->asaas_api_key);
    }

    public function estaBloqueada(): bool
    {
        return $this->status === StatusClinica::Bloqueada;
    }
}
