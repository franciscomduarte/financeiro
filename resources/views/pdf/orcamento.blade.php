@php $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Orçamento {{ $orcamento->codigo() }}</title>@include('pdf._estilo')
<style>
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { text-align: left; font-size: 9px; color: #78716c; border-bottom: 1px solid #e7e5e4; padding: 6px 4px; }
    td { padding: 7px 4px; border-bottom: 1px solid #f5f5f4; }
    .num { text-align: right; white-space: nowrap; }
    .total td { border: 0; padding-top: 4px; }
    .grande { font-size: 14px; font-weight: bold; }
</style>
</head>
<body>
    @include('pdf._topo')

    <h1>Orçamento {{ $orcamento->codigo() }}</h1>
    <div class="sub">
        Paciente: {{ $orcamento->paciente->nome }} · Emitido em {{ $orcamento->created_at->timezone(config('clinica.fuso_horario'))->format('d/m/Y') }}
        · Válido até {{ $orcamento->validade->format('d/m/Y') }}
    </div>

    <table>
        <thead><tr><th>Procedimento</th><th class="num">Sessões</th><th class="num">Valor unitário</th><th class="num">Subtotal</th></tr></thead>
        <tbody>
            @foreach ($orcamento->itens as $item)
                <tr>
                    <td>{{ $item->descricao }}</td>
                    <td class="num">{{ $item->quantidade }}</td>
                    <td class="num">{{ $brl($item->valor_unitario) }}</td>
                    <td class="num">{{ $brl($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tbody class="total">
            @if ((float) $orcamento->desconto > 0)
                <tr><td colspan="3" class="num">Subtotal</td><td class="num">{{ $brl($orcamento->subtotal) }}</td></tr>
                <tr><td colspan="3" class="num">Desconto</td><td class="num">− {{ $brl($orcamento->desconto) }}</td></tr>
            @endif
            <tr><td colspan="3" class="num grande">Total</td><td class="num grande">{{ $brl($orcamento->total) }}</td></tr>
            @if ($orcamento->forma_pagamento)
                <tr><td colspan="4" class="num" style="color:#78716c">Forma de pagamento: {{ $orcamento->forma_pagamento->label() }}</td></tr>
            @endif
        </tbody>
    </table>

    @if ($orcamento->observacoes)
        <p style="margin-top:18px"><strong>Observações</strong><br>{!! nl2br(e($orcamento->observacoes)) !!}</p>
    @endif

    <div class="rodape">Orçamento sujeito a avaliação. Valores válidos até {{ $orcamento->validade->format('d/m/Y') }}.</div>
</body>
</html>
