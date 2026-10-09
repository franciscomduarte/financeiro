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
        'nfse_token',
        'nfse_homologacao',
        'inscricao_municipal',
        'codigo_municipio',
        'nfse_item_lista_servico',
        'nfse_codigo_tributario',
        'nfse_aliquota_iss',
        'nfse_optante_simples',
        'nfse_discriminacao_padrao',
        'nfse_padrao',
        'nfse_codigo_tributacao_nacional',
        'nfse_webhook_token',
        'nfse_webhook_em',
        'nfse_webhook_homologacao',
    ];

    /** Segredos nunca vão para arrays/JSON. */
    protected $hidden = ['evolution_api_key', 'asaas_api_key', 'asaas_webhook_token', 'nfse_token', 'nfse_webhook_token'];

    protected $casts = [
        'status'              => StatusClinica::class,
        'teste_ate'           => 'date',
        'aliquota_imposto'    => 'decimal:2',
        'asaas_sandbox'       => 'boolean',
        'evolution_api_key'   => 'encrypted',
        'asaas_api_key'       => 'encrypted',
        'asaas_webhook_token' => 'encrypted',
        'nfse_token'          => 'encrypted',
        'nfse_webhook_token'  => 'encrypted',
        'nfse_webhook_em'     => 'datetime',
        'nfse_webhook_homologacao' => 'boolean',
        'nfse_homologacao'    => 'boolean',
        'nfse_optante_simples' => 'boolean',
        'nfse_aliquota_iss'   => 'decimal:2',
        'nfse_padrao'         => \App\Enums\PadraoNfse::class,
        'aviso_teste_3_dias_em' => 'datetime',
        'aviso_teste_fim_em'    => 'datetime',
        'primeiros_passos_dispensado_em' => 'datetime',
        'ultimo_acesso_em'      => 'datetime',
        'ativada_em'            => 'datetime',
    ];

    public function eventos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClinicaEvento::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clinica_user')
            ->withPivot('papel')
            ->withTimestamps();
    }

    /** Aviso automático da Focus cadastrado para o ambiente atual (homologação ou produção). */
    public function avisoNfseAtivo(): bool
    {
        return $this->nfse_webhook_em !== null && filled($this->nfse_webhook_token)
            && $this->nfse_webhook_homologacao === (bool) $this->nfse_homologacao;
    }

    /** O que falta preencher para emitir NFS-e (vazio = pronto). */
    public function pendenciasNfse(): array
    {
        $nacional = $this->nfse_padrao === \App\Enums\PadraoNfse::Nacional;

        return array_keys(array_filter([
            'Token da Focus NFe'          => ! filled($this->nfse_token),
            'CNPJ'                        => strlen((string) preg_replace('/\D/', '', (string) $this->cnpj)) !== 14,
            'Inscrição municipal'         => ! $nacional && ! filled($this->inscricao_municipal),
            'Código IBGE do município'    => ! preg_match('/^\d{7}$/', (string) $this->codigo_municipio),
            'Item da lista de serviço'    => ! $nacional && ! filled($this->nfse_item_lista_servico),
            'Código de tributação nacional' => $nacional && ! preg_match('/^\d{6}$/', (string) $this->nfse_codigo_tributacao_nacional),
            'Alíquota do ISS'             => ! $nacional && $this->nfse_aliquota_iss === null,
        ]));
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

    public function emTeste(): bool
    {
        return $this->status === StatusClinica::Teste;
    }

    /** Teste grátis terminou (o último dia de uso é teste_ate): só leitura até a assinatura. */
    public function somenteLeitura(): bool
    {
        return $this->emTeste() && $this->teste_ate !== null && $this->teste_ate->lt(today());
    }

    /** Dias de teste que ainda restam contando hoje (0 quando já terminou); null fora do teste. */
    public function diasRestantesTeste(): ?int
    {
        if (! $this->emTeste() || $this->teste_ate === null) {
            return null;
        }

        return max(0, (int) today()->diffInDays($this->teste_ate, false) + 1);
    }
}
