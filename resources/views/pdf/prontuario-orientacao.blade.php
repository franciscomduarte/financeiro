<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>{{ $orientacao->titulo }}</title>@include('pdf._estilo')</head>
<body>
    @include('pdf._topo')

    <h1>{{ $orientacao->titulo }}</h1>
    <div class="sub">Paciente: {{ $orientacao->paciente->nome }} · {{ $orientacao->created_at->timezone(config('clinica.fuso_horario'))->format('d/m/Y') }}</div>

    <div class="texto">{!! nl2br(e($orientacao->texto)) !!}</div>

    <div class="assinatura">
        <div class="linha">{{ $orientacao->autor?->name ?? '' }}</div>
    </div>
</body>
</html>
