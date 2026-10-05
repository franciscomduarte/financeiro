<div>

    {{-- ─── Avisos ──────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition:leave="transition duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 inset-x-4 z-[70] sm:left-auto sm:right-4 sm:max-w-sm"
            role="status"
        >
            <div class="bg-surface border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
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
            class="fixed top-4 inset-x-4 z-[70] sm:left-auto sm:right-4 sm:max-w-sm"
            role="alert"
        >
            <div class="bg-surface border border-red-200 text-red-700 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Cabeçalho ─────────────────────────────────────── --}}
    <x-ui.page-header titulo="Taxas de cartão" subtitulo="Defina quanto a maquininha desconta em cada forma de pagamento.">
        @if (count($taxas) > 0)
        <x-slot:acoes>
            <button
                type="button"
                wire:click="salvarTodas"
                wire:loading.attr="disabled"
                class="btn-primary"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span wire:loading.remove wire:target="salvarTodas">Salvar todas as taxas</span>
                <span wire:loading wire:target="salvarTodas">Salvando…</span>
            </button>
        </x-slot:acoes>
        @endif
    </x-ui.page-header>

    {{-- ─── Explicação ─────────────────────────────────── --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-6 text-sm text-blue-700">
        <svg class="w-5 h-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <p>Usamos essas taxas para calcular o valor líquido de cada lançamento. Formas de pagamento <strong class="font-semibold">desativadas</strong> contam como 0%.</p>
    </div>

    @if (count($taxas) === 0)
        <div class="card">
            <x-ui.empty-state
                titulo="Nenhuma taxa cadastrada"
                texto="As taxas aparecem aqui para você ajustar o desconto de cada forma de pagamento. Se a lista continuar vazia, fale com o suporte."
                icone="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
        </div>
    @else

        {{-- ─── Cartões ────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($taxas as $id => $taxa)
                @php
                    $nomeTaxa = \App\Enums\FormaPagamento::tryFrom($taxa['modalidade'])?->label()
                        ?? ucfirst(str_replace('_', ' ', $taxa['modalidade']));
                @endphp
                <div wire:key="taxa-{{ $id }}" class="card p-4 flex flex-col gap-3 {{ !$taxa['ativo'] ? 'opacity-60' : '' }}">

                    {{-- Nome + ativar/desativar --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-stone-900 truncate">{{ $nomeTaxa }}</p>
                            <p class="text-xs text-stone-500">{{ $taxa['ativo'] ? 'Ativa' : 'Desativada' }}</p>
                        </div>
                        {{-- Interruptor (área de toque de 44px) --}}
                        <button
                            type="button"
                            wire:click="toggleAtivo('{{ $id }}')"
                            class="group -mr-2 inline-flex h-11 w-14 shrink-0 items-center justify-center rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"
                            role="switch"
                            aria-checked="{{ $taxa['ativo'] ? 'true' : 'false' }}"
                            aria-label="{{ $taxa['ativo'] ? 'Desativar' : 'Ativar' }} {{ $nomeTaxa }}"
                        >
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $taxa['ativo'] ? 'bg-rose-600' : 'bg-stone-300' }}">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-surface shadow transition-transform
                                    {{ $taxa['ativo'] ? 'translate-x-[22px]' : 'translate-x-0.5' }}">
                                </span>
                            </span>
                        </button>
                    </div>

                    {{-- Percentual --}}
                    <div>
                        <label for="taxa-{{ $id }}" class="sr-only">Taxa de {{ $nomeTaxa }} (%)</label>
                        <div class="flex items-center gap-2 rounded-xl border bg-stone-50 px-3.5 min-h-[44px] focus-within:border-rose-300 focus-within:ring-4 focus-within:ring-rose-100 @error("taxas.{$id}.percentual") border-red-300 @else border-stone-200 @enderror">
                            <span class="text-sm text-stone-500 shrink-0">Taxa</span>
                            <input
                                id="taxa-{{ $id }}"
                                type="number"
                                inputmode="decimal"
                                wire:model="taxas.{{ $id }}.percentual"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="Ex.: 2,5"
                                class="flex-1 bg-transparent text-right text-lg font-semibold text-stone-900 focus:outline-none w-0 min-w-0 tabular-nums @error("taxas.{$id}.percentual") text-red-600 @enderror"
                            >
                            <span class="text-sm font-medium text-stone-500 shrink-0">%</span>
                        </div>
                        @error("taxas.{$id}.percentual")
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Salvar --}}
                    <button
                        type="button"
                        wire:click="salvarTaxa('{{ $id }}')"
                        wire:loading.attr="disabled"
                        wire:target="salvarTaxa('{{ $id }}')"
                        class="btn-secondary w-full"
                    >
                        <span wire:loading.remove wire:target="salvarTaxa('{{ $id }}')">Salvar taxa</span>
                        <span wire:loading wire:target="salvarTaxa('{{ $id }}')">Salvando…</span>
                    </button>

                </div>
            @endforeach
        </div>

    @endif

</div>
