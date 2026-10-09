{{-- Modal de novo bloqueio (Agenda e Profissionais e horários). Usa o trait Concerns\FormularioBloqueio. --}}
    @if ($modalBloqueio)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalBloqueio"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Novo bloqueio</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Ninguém consegue agendar no período bloqueado.</p>
                </div>
                <button wire:click="fecharModalBloqueio" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                @php $profsBloqueio = $this->profissionaisParaBloqueio(); @endphp
                @if (app(\App\Support\EscopoProfissional::class)->profissionalId())
                    <p class="text-sm text-stone-600">Agenda de <span class="font-semibold text-stone-900">{{ $profsBloqueio->first()?->nome }}</span></p>
                @else
                    <div>
                        <label for="bloq-profissional" class="label">Profissional <span class="text-rose-600">*</span></label>
                        <select id="bloq-profissional" wire:model="bloqProfissionalId"
                                class="input @error('bloqProfissionalId') !border-red-300 @enderror">
                            <option value="{{ $this::BLOQUEIO_TODOS }}">Todos os profissionais</option>
                            @foreach ($profsBloqueio as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                        @error('bloqProfissionalId') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="grid grid-cols-2 rounded-xl bg-stone-100 p-1">
                    <button type="button" wire:click="$set('bloqDiaInteiro', true)"
                            class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ $bloqDiaInteiro ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500' }}">
                        Dia inteiro
                    </button>
                    <button type="button" wire:click="$set('bloqDiaInteiro', false)"
                            class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ ! $bloqDiaInteiro ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500' }}">
                        Algumas horas
                    </button>
                </div>

                @if ($bloqDiaInteiro)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bloq-data-inicio" class="label">De <span class="text-rose-600">*</span></label>
                            <input id="bloq-data-inicio" type="date" wire:model.live="bloqDataInicio"
                                   class="input @error('bloqDataInicio') !border-red-300 @enderror">
                            @error('bloqDataInicio') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="bloq-data-fim" class="label">Até <span class="text-rose-600">*</span></label>
                            <input id="bloq-data-fim" type="date" wire:model="bloqDataFim" min="{{ $bloqDataInicio }}"
                                   class="input @error('bloqDataFim') !border-red-300 @enderror">
                            @error('bloqDataFim') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @else
                    <div>
                        <label for="bloq-data" class="label">Data <span class="text-rose-600">*</span></label>
                        <input id="bloq-data" type="date" wire:model="bloqDataInicio"
                               class="input @error('bloqDataInicio') !border-red-300 @enderror">
                        @error('bloqDataInicio') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bloq-hora-inicio" class="label">Das <span class="text-rose-600">*</span></label>
                            <input id="bloq-hora-inicio" type="time" wire:model="bloqHoraInicio" step="900"
                                   class="input @error('bloqHoraInicio') !border-red-300 @enderror">
                            @error('bloqHoraInicio') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="bloq-hora-fim" class="label">Até <span class="text-rose-600">*</span></label>
                            <input id="bloq-hora-fim" type="time" wire:model="bloqHoraFim" step="900"
                                   class="input @error('bloqHoraFim') !border-red-300 @enderror">
                            @error('bloqHoraFim') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
                <div>
                    <label for="bloq-motivo" class="label">Motivo</label>
                    <input id="bloq-motivo" type="text" wire:model="bloqMotivo" maxlength="500" placeholder="Ex.: Férias, feriado, congresso"
                           class="input @error('bloqMotivo') !border-red-300 @enderror">
                    @error('bloqMotivo') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalBloqueio" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="salvarBloqueio" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="salvarBloqueio">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar bloqueio
                </button>
            </div>
        </div>
    </div>
    @endif
