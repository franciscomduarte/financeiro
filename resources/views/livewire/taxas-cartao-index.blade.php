<div>

    {{-- ─── Flash Messages ──────────────────────────────── --}}
    @if ($flashSucesso)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition:leave="transition duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 right-4 z-50 max-w-sm"
        >
            <div class="bg-white border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                {{ $flashSucesso }}
            </div>
        </div>
    @endif

    @if ($flashErro)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            x-transition:leave="transition duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 right-4 z-50 max-w-sm"
        >
            <div class="bg-white border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Header ───────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Taxas de Cartão</h1>
            <p class="text-sm text-stone-500 mt-0.5">Configure os percentuais de desconto por modalidade de pagamento</p>
        </div>
        <button
            wire:click="salvarTodas"
            wire:loading.attr="disabled"
            class="flex items-center gap-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm shadow-rose-200 transition-colors disabled:opacity-60"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span wire:loading.remove wire:target="salvarTodas">Salvar Todas</span>
            <span wire:loading wire:target="salvarTodas">Salvando...</span>
        </button>
    </div>

    {{-- ─── Info banner ─────────────────────────────────── --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-6 text-sm text-blue-700">
        <svg class="w-4 h-4 mt-0.5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <p>As taxas são usadas no cálculo automático do valor líquido das transações. Modalidades <strong>inativas</strong> retornam taxa 0% no cálculo.</p>
    </div>

    @if (count($taxas) === 0)
        <div class="bg-white rounded-2xl border border-stone-100 shadow-sm p-16 text-center text-stone-400">
            <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <p class="text-sm font-medium text-stone-500">Nenhuma taxa cadastrada</p>
        </div>
    @else

        {{-- ─── Cards grid ────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($taxas as $id => $taxa)
                <div class="bg-white rounded-2xl border border-stone-100 shadow-sm p-4 flex flex-col gap-3 {{ !$taxa['ativo'] ? 'opacity-60' : '' }}">

                    {{-- Card top row: icon + name + toggle --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-stone-800 truncate">
                                {{ str_replace('_', ' ', ucwords(str_replace('_', ' ', $taxa['modalidade']))) }}
                            </p>
                            <p class="text-xs text-stone-400 font-mono">{{ $taxa['modalidade'] }}</p>
                        </div>
                        {{-- Toggle --}}
                        <button
                            wire:click="toggleAtivo('{{ $id }}')"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-1 shrink-0
                                {{ $taxa['ativo'] ? 'bg-rose-600' : 'bg-stone-200' }}"
                            role="switch"
                            aria-checked="{{ $taxa['ativo'] ? 'true' : 'false' }}"
                        >
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                {{ $taxa['ativo'] ? 'translate-x-[18px]' : 'translate-x-0.5' }}">
                            </span>
                        </button>
                    </div>

                    {{-- Percentage input --}}
                    <div class="flex items-center gap-2 bg-stone-50 rounded-xl px-3 py-2">
                        <span class="text-xs font-medium text-stone-500 shrink-0">Taxa</span>
                        <input
                            type="number"
                            wire:model="taxas.{{ $id }}.percentual"
                            step="0.01"
                            min="0"
                            max="100"
                            class="flex-1 bg-transparent text-right text-lg font-bold text-stone-800 focus:outline-none w-0 min-w-0 tabular-nums @error("taxas.{$id}.percentual") text-red-600 @enderror"
                        >
                        <span class="text-sm font-semibold text-stone-500 shrink-0">%</span>
                    </div>

                    @error("taxas.{$id}.percentual")
                        <p class="text-xs text-red-500 -mt-1">{{ $message }}</p>
                    @enderror

                    {{-- Save button --}}
                    <button
                        wire:click="salvarTaxa('{{ $id }}')"
                        wire:loading.attr="disabled"
                        wire:target="salvarTaxa('{{ $id }}')"
                        class="w-full bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-xl text-xs font-semibold transition-colors disabled:opacity-60 flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" wire:loading.remove wire:target="salvarTaxa('{{ $id }}')">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span wire:loading.remove wire:target="salvarTaxa('{{ $id }}')">Salvar</span>
                        <span wire:loading wire:target="salvarTaxa('{{ $id }}')">Salvando...</span>
                    </button>

                </div>
            @endforeach
        </div>

    @endif

</div>
