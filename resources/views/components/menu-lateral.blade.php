{{-- Estado vem do layout: recolhido (barra de ícones no desktop), desktop (largura ≥ md), menuAberto (gaveta no celular) --}}
<div class="flex h-full flex-col">
    {{-- Clínica ativa + botão hambúrguer --}}
    <div class="h-16 px-3 flex items-center gap-2 shrink-0 border-b border-stone-200/70">
        <div class="flex min-w-0 flex-1 items-center gap-2.5" :class="recolhido && desktop && 'md:hidden'">
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

        {{-- Hambúrguer: no desktop recolhe/expande; no celular fecha a gaveta --}}
        <button type="button" @click="desktop ? alternarMenu() : (menuAberto = false)"
                class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-stone-800"
                :class="recolhido && desktop && 'mx-auto'"
                :aria-label="desktop ? (recolhido ? 'Expandir menu' : 'Recolher menu') : 'Fechar menu'"
                :title="desktop ? (recolhido ? 'Expandir menu' : 'Recolher menu') : 'Fechar menu'">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </div>

    {{-- Navegação: grupos recolhíveis (abre o grupo da tela atual); na barra de ícones, todos os ícones --}}
    <nav class="flex-1 overflow-y-auto px-3 py-3" aria-label="Menu principal">
        @foreach ($grupos as $grupo)
            @php($sempreAberto = $grupo['titulo'] === null || $grupo['ativo'])
            <div class="{{ $loop->first ? '' : 'mt-1' }}"
                 x-data="{ aberto: {{ $sempreAberto ? 'true' : 'false' }} || (() => { try { return localStorage.getItem('menu-grupo-{{ $grupo['chave'] }}') === '1' } catch (e) { return false } })() }">
                @if ($grupo['titulo'])
                    <button type="button" x-show="!(recolhido && desktop)"
                            @click="aberto = !aberto; try { localStorage.setItem('menu-grupo-{{ $grupo['chave'] }}', aberto ? '1' : '0') } catch (e) {}"
                            class="flex w-full min-h-[40px] md:min-h-[32px] items-center justify-between rounded-lg px-3 text-[11px] font-semibold uppercase tracking-wider text-stone-400 hover:text-stone-600"
                            :aria-expanded="aberto">
                        {{ $grupo['titulo'] }}
                        <svg class="w-3.5 h-3.5 transition-transform" :class="aberto && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    <div x-show="recolhido && desktop" x-cloak class="mx-2 my-2 border-t border-stone-200/70"></div>
                @endif
                <ul class="space-y-0.5" x-show="aberto || (recolhido && desktop)" @if (! $sempreAberto) x-cloak @endif>
                    @foreach ($grupo['itens'] as $item)
                        <li>
                            <a href="{{ route($item['rota']) }}" title="{{ $item['rotulo'] }}"
                               @if ($item['ativo']) aria-current="page" @endif
                               class="group flex min-h-[44px] md:min-h-[36px] items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors
                                      {{ $item['ativo'] ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}"
                               :class="recolhido && desktop && 'justify-center px-0'">
                                <svg class="w-[18px] h-[18px] shrink-0 {{ $item['ativo'] ? 'text-rose-600' : 'text-stone-400 group-hover:text-stone-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icone'] }}" />
                                </svg>
                                <span class="truncate" :class="recolhido && desktop && 'sr-only'">{{ $item['rotulo'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Usuário: conta, configurações e sair --}}
    <div class="shrink-0 border-t border-stone-200/70 p-3 relative" x-data="{ aberto: false }" @click.outside="aberto = false" @keydown.escape="aberto = false">
        <button type="button" @click="aberto = !aberto" :aria-expanded="aberto" aria-haspopup="true"
                class="flex w-full min-h-[48px] items-center gap-2.5 rounded-lg p-1.5 text-left hover:bg-stone-100"
                :class="recolhido && desktop && 'justify-center'"
                title="{{ auth()->user()?->name }}">
            <span class="w-8 h-8 rounded-full bg-stone-200 text-stone-700 flex items-center justify-center shrink-0 text-xs font-semibold">
                {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 1)) }}
            </span>
            <span class="min-w-0 flex-1" :class="recolhido && desktop && 'md:hidden'">
                <span class="block text-sm font-medium text-stone-800 truncate leading-tight">{{ auth()->user()?->name }}</span>
                <span class="block text-xs text-stone-500 truncate leading-tight">Conta e configurações</span>
            </span>
            <svg class="w-4 h-4 shrink-0 text-stone-400" :class="recolhido && desktop && 'md:hidden'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>
        </button>

        <div x-show="aberto" x-cloak x-transition
             class="absolute z-50 rounded-xl border border-stone-200 bg-surface p-1 shadow-lg"
             :class="recolhido && desktop ? 'left-full bottom-3 ml-2 w-64' : 'left-3 right-3 bottom-full mb-1'">
            <p class="px-3 pt-2 pb-1 text-xs text-stone-500 truncate">{{ auth()->user()?->email }}</p>
            @foreach ($menuUsuario as $item)
                <a href="{{ route($item['rota']) }}"
                   class="flex min-h-[44px] md:min-h-[38px] items-center gap-3 rounded-lg px-3 text-sm {{ $item['ativo'] ? 'bg-rose-50 text-rose-700' : 'text-stone-700 hover:bg-stone-100' }}">
                    <svg class="w-[18px] h-[18px] shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icone'] }}" /></svg>
                    {{ $item['rotulo'] }}
                </a>
            @endforeach
            <div class="my-1 border-t border-stone-200/70"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex min-h-[44px] md:min-h-[38px] w-full items-center gap-3 rounded-lg px-3 text-sm text-stone-700 hover:bg-stone-100">
                    <svg class="w-[18px] h-[18px] shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                    Sair
                </button>
            </form>
        </div>
    </div>
</div>
