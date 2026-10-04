<div class="space-y-4" @voice-dados.window="$wire.preencherVoz($event.detail)">

    {{-- ── Botão de voz ──────────────────────────────────────── --}}
    <div x-data="vozTransacao()" class="flex items-start gap-3 p-3 bg-stone-50 rounded-xl border border-stone-100">
        <button
            type="button"
            @click="iniciar()"
            :disabled="processando"
            :class="gravando ? 'bg-red-600 ring-2 ring-red-300 ring-offset-1 animate-pulse' : (processando ? 'bg-stone-400 cursor-not-allowed' : 'bg-rose-600 hover:bg-rose-700')"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-white text-xs font-semibold transition-all shrink-0 shadow-sm"
        >
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
            </svg>
            <span x-text="gravando ? 'Gravando...' : (processando ? 'Processando...' : 'Preencher por Voz')"></span>
        </button>

        <div class="flex-1 min-w-0">
            <p x-show="!gravando && !processando && !transcricao && !erro" class="text-xs text-stone-400 leading-relaxed">
                Clique, fale a transação e diga <strong class="text-stone-500">"gravar"</strong> para finalizar. Ex: <em>"Gastei 150 reais de energia elétrica no pix, já tá pago... gravar"</em>
            </p>
            <p x-show="gravando" class="text-xs text-red-600 font-medium">Fale a transação e diga <strong>"gravar"</strong> para finalizar</p>
            <p x-show="processando" class="text-xs text-stone-500">Extraindo dados com IA...</p>
            <p x-show="gravando && parcial" x-text="parcial" class="text-xs text-stone-500 italic leading-relaxed"></p>
            <p x-show="transcricao && !processando && !gravando" x-text="'“' + transcricao + '”'" class="text-xs text-stone-600 italic leading-relaxed"></p>
            <p x-show="erro" x-text="erro" class="text-xs text-red-500 font-medium"></p>
        </div>
    </div>

    {{-- Erros de validação --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-100 rounded-lg p-3">
            <ul class="text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tipo / Fase --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Tipo <span class="text-red-500">*</span></label>
            <select wire:model.live="tipo" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white @error('tipo') border-red-300 @enderror">
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Fase <span class="text-red-500">*</span></label>
            <select wire:model="fase" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white @error('fase') border-red-300 @enderror">
                <option value="implantacao">Implantação</option>
                <option value="operacao">Operação</option>
            </select>
        </div>
    </div>

    {{-- Categoria / Descrição --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Categoria <span class="text-red-500">*</span></label>
            <select wire:model="categoria" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white @error('categoria') border-red-300 @enderror">
                <option value="">Selecione...</option>
                @foreach ($this->categorias as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Descrição <span class="text-red-500">*</span></label>
            <input
                type="text"
                wire:model="descricao"
                placeholder="Descreva a transação"
                class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 @error('descricao') border-red-300 @enderror"
            >
        </div>
    </div>

    {{-- Paciente (só para entrada) --}}
    @if ($tipo === 'entrada')
        <div x-data="{ aberto: false }">
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Paciente</label>
            <div class="relative">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="pacienteBusca"
                    x-on:focus="aberto = true"
                    x-on:blur="setTimeout(() => aberto = false, 200)"
                    placeholder="Buscar paciente por nome ou CPF..."
                    autocomplete="off"
                    class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"
                >
                @if ($this->pacientesFiltrados->isNotEmpty())
                    <div x-show="aberto"
                         class="absolute z-20 w-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        @foreach ($this->pacientesFiltrados as $p)
                            <button type="button"
                                    wire:click="selecionarPaciente('{{ $p->id }}', '{{ addslashes($p->nome) }}')"
                                    class="w-full text-left px-3 py-2.5 text-sm hover:bg-stone-50 flex flex-col border-b border-stone-50 last:border-0">
                                <span class="font-medium text-stone-800">{{ $p->nome }}</span>
                                @if ($p->cpf)
                                    <span class="text-xs text-stone-400">CPF: {{ $p->cpf }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($pacienteId)
                <p class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Paciente vinculado: {{ $pacienteBusca }}
                </p>
            @endif
        </div>
    @endif

    {{-- Valor Bruto / Forma de Pagamento --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Valor Bruto (R$) <span class="text-red-500">*</span></label>
            <input
                type="number"
                wire:model.live="valorBruto"
                step="0.01"
                min="0"
                placeholder="0,00"
                class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 @error('valorBruto') border-red-300 @enderror"
            >
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Forma de Pagamento <span class="text-red-500">*</span></label>
            <select wire:model.live="formaPagamento" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white @error('formaPagamento') border-red-300 @enderror">
                @foreach ($formasPagamento as $fp)
                    <option value="{{ $fp->value }}">{{ $fp->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Valores Calculados --}}
    @if ($taxaOperacional !== null)
        <div class="bg-stone-50 rounded-xl p-4 space-y-1.5">
            <h4 class="text-xs font-semibold text-stone-500 uppercase tracking-wide mb-2">Valores Calculados</h4>
            <div class="flex justify-between text-sm">
                <span class="text-stone-500">Taxa Operacional</span>
                <span class="text-red-600 font-medium">- R$ {{ number_format($taxaOperacional, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-stone-500">Imposto Estimado (6%)</span>
                <span class="text-red-600 font-medium">- R$ {{ number_format($impostoEstimado, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-sm pt-2 border-t border-stone-200 font-semibold">
                <span class="text-stone-700">Valor Líquido</span>
                <span class="text-emerald-700">R$ {{ number_format($valorLiquido, 2, ',', '.') }}</span>
            </div>
        </div>
    @endif

    {{-- Datas --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">Data de Competência <span class="text-red-500">*</span></label>
            <input
                type="date"
                wire:model="dataCompetencia"
                class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 @error('dataCompetencia') border-red-300 @enderror"
            >
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1.5">
                Data de Pagamento @if ($status === 'pago') <span class="text-red-500">*</span> @endif
            </label>
            <input
                type="date"
                wire:model="dataPagamento"
                class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 @error('dataPagamento') border-red-300 @enderror"
            >
            @error('dataPagamento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Status (recorrência fica oculta até existir geração automática dos lançamentos) --}}
    <div>
        <label class="block text-xs font-medium text-stone-600 mb-1.5">Status <span class="text-red-500">*</span></label>
        <select wire:model.live="status" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white @error('status') border-red-300 @enderror">
            @foreach ($statusEnum as $st)
                <option value="{{ $st->value }}">{{ $st->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Observações --}}
    <div>
        <label class="block text-xs font-medium text-stone-600 mb-1.5">Observações</label>
        <textarea
            wire:model="observacoes"
            rows="3"
            placeholder="Observações adicionais..."
            class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 resize-none"
        ></textarea>
    </div>

</div>
