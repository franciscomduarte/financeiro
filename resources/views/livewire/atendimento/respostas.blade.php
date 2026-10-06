{{-- Respostas de uma ficha preenchida (somente leitura). Parâmetro: $ficha (AtendimentoFicha) --}}
<dl class="space-y-3">
    @foreach ($ficha->campos as $campo)
        @php
            $tipo  = \App\Enums\TipoCampoFicha::tryFrom($campo['tipo']);
            $valor = $ficha->respostas[$campo['id']] ?? null;
            $vazio = $valor === null || $valor === '' || $valor === [];
        @endphp
        @if ($tipo === \App\Enums\TipoCampoFicha::Titulo)
            <dt class="pt-1 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ $campo['rotulo'] }}</dt>
        @elseif (! $vazio)
            <div>
                <dt class="text-xs font-medium text-stone-500">{{ $campo['rotulo'] }}</dt>
                <dd class="text-sm text-stone-800">
                    @if ($tipo === \App\Enums\TipoCampoFicha::TextoRico)
                        <div class="texto-rico">{!! \App\Support\HtmlSeguro::limpar((string) $valor) !!}</div>
                    @elseif ($tipo === \App\Enums\TipoCampoFicha::Multipla)
                        {{ implode(', ', (array) $valor) }}
                    @elseif ($tipo === \App\Enums\TipoCampoFicha::SimNao)
                        {{ $valor === 'sim' ? 'Sim' : 'Não' }}
                    @elseif ($tipo === \App\Enums\TipoCampoFicha::Data)
                        {{ \Illuminate\Support\Carbon::parse((string) $valor)->format('d/m/Y') }}
                    @else
                        {{ $valor }}
                    @endif
                </dd>
            </div>
        @endif
    @endforeach
</dl>
