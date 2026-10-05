<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>{{ $termo->titulo }}</title>@include('pdf._estilo')</head>
<body>
    @include('pdf._topo')

    <h1>{{ $termo->titulo }}</h1>
    <div class="sub">Paciente: {{ $termo->paciente->nome }}@if ($termo->paciente->cpf) · CPF {{ $termo->paciente->cpf }}@endif</div>

    <div class="texto">{!! nl2br(e($termo->conteudo)) !!}</div>

    <div class="assinatura">
        @if ($assinatura)
            <img src="{{ $assinatura }}" alt="Assinatura">
        @endif
        <div class="linha">{{ $termo->assinante_nome }}</div>
        <div class="dados-clinica">Assinado em {{ $termo->assinado_em->timezone(config('clinica.fuso_horario'))->format('d/m/Y \à\s H:i') }}</div>
    </div>

    <div class="rodape">
        Assinatura eletrônica feita na tela, na clínica, com registro de {{ $termo->autor?->name ?? 'usuário removido' }}@if ($termo->ip) (IP {{ $termo->ip }})@endif.<br>
        Código de verificação (SHA-256): {{ $termo->hash }}
    </div>
</body>
</html>
