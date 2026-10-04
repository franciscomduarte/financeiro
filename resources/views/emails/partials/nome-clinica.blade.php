{{-- Identidade da clínica no topo dos e-mails (logo, se houver, e nome) --}}
@if ($clinicaAtual?->logoUrl())
    <img src="{{ $clinicaAtual->logoUrl() }}" alt="{{ $clinicaAtual->nome }}" style="max-height:48px; max-width:180px; margin:8px auto 4px; display:block;">
@endif
<p>{{ $clinicaAtual?->nome ?? config('app.name') }}</p>
