<div class="topo">
    <div class="clinica">{{ $clinica?->nome ?? config('app.name') }}</div>
    <div class="dados-clinica">
        {{ collect([$clinica?->razao_social, $clinica?->cnpj ? 'CNPJ ' . $clinica->cnpj : null, $clinica?->telefone, $clinica?->endereco])->filter()->implode(' · ') }}
    </div>
</div>
