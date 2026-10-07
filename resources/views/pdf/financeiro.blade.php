<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>{{ $titulo }}</title>@include('pdf._estilo')
<style>
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { text-align: left; font-size: 9px; color: #78716c; border-bottom: 1px solid #e7e5e4; padding: 5px 4px; }
    td { padding: 5px 4px; border-bottom: 1px solid #f5f5f4; font-size: 10px; }
    td.num, th.num { text-align: right; white-space: nowrap; }
    tr.total td { font-weight: bold; background: #fafaf9; }
    tr.sub td { color: #57534e; font-size: 9px; }
</style>
</head>
<body>
    @include('pdf._topo')
    <h1>{{ $titulo }}</h1>
    <div class="sub">{{ $subtitulo ?? '' }}</div>
    @php $cab = array_shift($linhas); @endphp
    <table>
        <thead><tr>@foreach ($cab as $i => $c)<th class="{{ $i ? 'num' : '' }}">{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($linhas as $l)
                @php $rotulo = (string) $l[0]; @endphp
                <tr class="{{ str_starts_with($rotulo, '=') || in_array($rotulo, ['Total', 'Saldo hoje', 'Saldo inicial'], true) ? 'total' : (str_starts_with($rotulo, '    ') ? 'sub' : '') }}">
                    @foreach ($l as $i => $c)
                        <td class="{{ $i ? 'num' : '' }}" @if ($i === 0 && str_starts_with($rotulo, '    ')) style="padding-left: 18px" @endif>{{ $i === 0 ? ltrim($rotulo) : $c }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="rodape">Gerado em {{ now()->format('d/m/Y H:i') }} · {{ config('app.name') }}</div>
</body>
</html>
