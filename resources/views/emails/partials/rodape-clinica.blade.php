{{-- Rodapé com assinatura e contatos da clínica --}}
<strong>{{ $clinicaAtual?->assinatura() ?? config('app.name') }}</strong>
@if ($clinicaAtual?->telefone || $clinicaAtual?->email_contato)
    <br>{{ collect([$clinicaAtual->telefone, $clinicaAtual->email_contato])->filter()->implode(' · ') }}
@endif
@if ($clinicaAtual?->endereco)
    <br>{{ $clinicaAtual->endereco }}
@endif
@if ($clinicaAtual?->razao_social || $clinicaAtual?->cnpj)
    <br>{{ collect([$clinicaAtual->razao_social, $clinicaAtual->cnpj ? 'CNPJ ' . $clinicaAtual->cnpj : null])->filter()->implode(' · ') }}
@endif
