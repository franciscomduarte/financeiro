{{-- Abas de Lançamentos: lista do dia a dia e os modelos que se repetem --}}
<nav class="mb-6 flex w-full gap-1 overflow-x-auto rounded-xl bg-stone-100 p-1 sm:w-fit" aria-label="Seções de lançamentos">
    @foreach ([['Lançamentos', 'transacoes.index'], ['Recorrências', 'transacoes.recorrencias']] as [$rotulo, $rota])
        <a href="{{ route($rota) }}" @if (request()->routeIs($rota)) aria-current="page" @endif
           class="flex min-h-[40px] items-center rounded-lg px-4 text-sm font-medium whitespace-nowrap transition-colors
                  {{ request()->routeIs($rota) ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
            {{ $rotulo }}
        </a>
    @endforeach
</nav>
