<div class="flex h-full flex-col">
    {{-- Clínica ativa (troca de clínica quando o usuário tem mais de uma) --}}
    <div class="h-16 px-4 flex items-center gap-3 shrink-0">
        @if ($clinica?->logoUrl())
            <img src="{{ $clinica->logoUrl() }}" alt="" class="w-9 h-9 rounded-xl object-contain shrink-0 bg-white ring-1 ring-stone-200">
        @else
            <div class="w-9 h-9 rounded-xl bg-rose-600 flex items-center justify-center shrink-0">
                <span class="text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($clinica?->nome ?? config('app.name'), 0, 1)) }}</span>
            </div>
        @endif

        <div class="relative min-w-0 flex-1" x-data="{ aberto: false }" @click.outside="aberto = false">
            <button type="button" @click="aberto = !aberto" @disabled($outrasClinicas->isEmpty())
                    class="flex w-full min-h-[44px] items-center gap-1 rounded-lg text-left {{ $outrasClinicas->isNotEmpty() ? 'cursor-pointer' : 'cursor-default' }}"
                    @if ($outrasClinicas->isNotEmpty()) aria-haspopup="true" :aria-expanded="aberto" @endif>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-stone-900 leading-tight truncate">{{ $clinica?->nome ?? config('app.name') }}</span>
                    <span class="block text-xs text-stone-500 leading-tight truncate">
                        {{ $outrasClinicas->isNotEmpty() ? 'Trocar de clínica' : config('app.name') }}
                    </span>
                </span>
                @if ($outrasClinicas->isNotEmpty())
                    <svg class="ml-auto h-4 w-4 shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>
                @endif
            </button>
            @if ($outrasClinicas->isNotEmpty())
                <div x-show="aberto" x-cloak x-transition
                     class="absolute left-0 right-0 top-full z-50 mt-1 rounded-xl border border-stone-200 bg-surface p-1 shadow-lg">
                    @foreach ($outrasClinicas as $outra)
                        <form method="POST" action="{{ route('clinicas.ativar', $outra->id) }}">
                            @csrf
                            <button type="submit" class="flex min-h-[44px] w-full items-center rounded-lg px-3 text-left text-sm text-stone-700 hover:bg-stone-100">
                                {{ $outra->nome }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Navegação --}}
    <nav class="flex-1 overflow-y-auto px-3 pb-4" aria-label="Menu principal">
        @foreach ($grupos as $grupo)
            <div class="{{ $loop->first ? 'mt-2' : 'mt-6' }}">
                @if ($grupo['titulo'])
                    <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-stone-400">{{ $grupo['titulo'] }}</p>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($grupo['itens'] as $item)
                        <li>
                            <a href="{{ route($item['rota']) }}"
                               @if ($item['ativo']) aria-current="page" @endif
                               class="group flex min-h-[40px] items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors
                                      {{ $item['ativo'] ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}">
                                <svg class="w-[18px] h-[18px] shrink-0 {{ $item['ativo'] ? 'text-rose-600' : 'text-stone-400 group-hover:text-stone-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icone'] }}" />
                                </svg>
                                {{ $item['rotulo'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Usuário --}}
    <div class="shrink-0 border-t border-stone-200/70 p-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('minha-conta') }}" class="flex min-w-0 flex-1 items-center gap-2.5 rounded-lg p-2 hover:bg-stone-100 {{ request()->routeIs('minha-conta') ? 'bg-stone-100' : '' }}" title="Minha conta">
                <span class="w-8 h-8 rounded-full bg-stone-200 text-stone-700 flex items-center justify-center shrink-0 text-xs font-semibold">
                    {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-stone-800 truncate leading-tight">{{ auth()->user()?->name }}</span>
                    <span class="block text-xs text-stone-500 truncate leading-tight">Minha conta</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sair" aria-label="Sair"
                        class="w-10 h-10 flex items-center justify-center rounded-lg text-stone-400 hover:text-stone-700 hover:bg-stone-100 transition-colors">
                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
