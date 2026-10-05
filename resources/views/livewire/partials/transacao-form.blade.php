<div class="space-y-5" @voice-dados.window="$wire.preencherVoz($event.detail)">

    {{-- ── Preencher por voz ─────────────────────────────────── --}}
    <div x-data="vozTransacao()" class="flex flex-col gap-3 p-3 sm:flex-row sm:items-start bg-rose-50 rounded-xl border border-rose-100">
        <button
            type="button"
            @click="iniciar()"
            :disabled="processando"
            :class="gravando ? 'bg-red-600 ring-2 ring-red-300 ring-offset-1 animate-pulse' : (processando ? 'bg-stone-400 cursor-not-allowed' : 'bg-rose-600 hover:bg-rose-500')"
            class="btn shrink-0 text-white shadow-sm"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
            </svg>
            <span x-text="gravando ? 'Ouvindo…' : (processando ? 'Processando…' : 'Preencher por voz')"></span>
        </button>

        <div class="flex-1 min-w-0 sm:pt-1" aria-live="polite">
            <p x-show="!gravando && !processando && !transcricao && !erro" class="text-sm text-stone-600 leading-relaxed">
                Toque, conte o lançamento e diga <strong class="font-semibold text-stone-800">"gravar"</strong> para terminar. Ex.: <em>"Gastei 150 reais de energia elétrica no pix, já tá pago… gravar"</em>
            </p>
            <p x-show="gravando" class="text-sm text-red-600 font-medium">Pode falar. Diga <strong>"gravar"</strong> quando terminar.</p>
            <p x-show="processando" class="text-sm text-stone-600">Entendendo o que você disse…</p>
            <p x-show="gravando && parcial" x-text="parcial" class="text-sm text-stone-500 italic leading-relaxed"></p>
            <p x-show="transcricao && !processando && !gravando" x-text="'“' + transcricao + '”'" class="text-sm text-stone-600 italic leading-relaxed"></p>
            <p x-show="erro" x-text="erro" class="text-sm text-red-600 font-medium"></p>
        </div>
    </div>

    {{-- Erros sem campo visível no formulário --}}
    @if ($errors->hasAny(['pacienteId', 'recorrencia']))
        <div class="bg-red-50 border border-red-100 rounded-xl p-3" role="alert">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach ($errors->get('pacienteId') as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->get('recorrencia') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tipo / Fase --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="tx-tipo" class="label">Tipo <span class="text-red-600">*</span></label>
            <select id="tx-tipo" wire:model.live="tipo" class="input @error('tipo') border-red-300 @enderror">
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>
            @error('tipo') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tx-fase" class="label">Fase <span class="text-red-600">*</span></label>
            <select id="tx-fase" wire:model="fase" class="input @error('fase') border-red-300 @enderror">
                <option value="implantacao">Implantação</option>
                <option value="operacao">Operação</option>
            </select>
            @error('fase') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Categoria / Descrição --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="tx-categoria" class="label">Categoria <span class="text-red-600">*</span></label>
            <select id="tx-categoria" wire:model="categoria" class="input @error('categoria') border-red-300 @enderror">
                <option value="">Selecione</option>
                @foreach ($this->categorias as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            @error('categoria') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tx-descricao" class="label">Descrição <span class="text-red-600">*</span></label>
            <input
                id="tx-descricao"
                type="text"
                wire:model="descricao"
                placeholder="Ex.: Limpeza de pele, Maria Silva"
                class="input @error('descricao') border-red-300 @enderror"
            >
            @error('descricao') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Paciente (só para entrada) --}}
    @if ($tipo === 'entrada')
        <div x-data="{ aberto: false }">
            <label for="tx-paciente" class="label">Paciente</label>
            <div class="relative">
                <input
                    id="tx-paciente"
                    type="search"
                    wire:model.live.debounce.300ms="pacienteBusca"
                    x-on:focus="aberto = true"
                    x-on:blur="setTimeout(() => aberto = false, 200)"
                    placeholder="Busque pelo nome ou CPF"
                    autocomplete="off"
                    class="input"
                >
                @if ($this->pacientesFiltrados->isNotEmpty())
                    <div x-show="aberto"
                         class="absolute z-20 w-full mt-1 bg-surface border border-stone-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                        @foreach ($this->pacientesFiltrados as $p)
                            <button type="button"
                                    wire:click="selecionarPaciente('{{ $p->id }}', '{{ addslashes($p->nome) }}')"
                                    class="w-full min-h-[44px] text-left px-3.5 py-2.5 text-sm hover:bg-stone-50 flex flex-col border-b border-stone-100 last:border-0">
                                <span class="font-medium text-stone-800">{{ $p->nome }}</span>
                                @if ($p->cpf)
                                    <span class="text-xs text-stone-500">CPF: {{ $p->cpf }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($pacienteId)
                <p class="hint text-emerald-700 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    Paciente vinculado: {{ $pacienteBusca }}
                </p>
            @else
                <p class="hint">Opcional. Vincule para enviar documentos ao paciente depois.</p>
            @endif
        </div>
    @endif

    {{-- Valor bruto / Forma de pagamento --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="tx-valor" class="label">Valor bruto (R$) <span class="text-red-600">*</span></label>
            <input
                id="tx-valor"
                type="number"
                inputmode="decimal"
                wire:model.live="valorBruto"
                step="0.01"
                min="0"
                placeholder="Ex.: 150,00"
                class="input tabular-nums @error('valorBruto') border-red-300 @enderror"
            >
            @error('valorBruto') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="tx-forma" class="label">Forma de pagamento <span class="text-red-600">*</span></label>
            <select id="tx-forma" wire:model.live="formaPagamento" class="input @error('formaPagamento') border-red-300 @enderror">
                @foreach ($formasPagamento as $fp)
                    <option value="{{ $fp->value }}">{{ $fp->label() }}</option>
                @endforeach
            </select>
            @error('formaPagamento') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Valores calculados --}}
    @if ($taxaOperacional !== null)
        <div class="bg-stone-50 rounded-xl p-4 space-y-1.5">
            <p class="text-sm font-medium text-stone-700 mb-2">Quanto sobra para a clínica</p>
            <div class="flex justify-between gap-4 text-sm">
                <span class="text-stone-500">Taxa do meio de pagamento</span>
                <span class="text-red-600 font-medium tabular-nums">− R$ {{ number_format($taxaOperacional, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-4 text-sm">
                <span class="text-stone-500">Imposto estimado ({{ rtrim(rtrim(number_format((float) ($clinicaAtual?->aliquota_imposto ?? 6), 2, ",", "."), "0"), ",") }}%)</span>
                <span class="text-red-600 font-medium tabular-nums">− R$ {{ number_format($impostoEstimado, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-4 text-sm pt-2 border-t border-stone-200 font-semibold">
                <span class="text-stone-700">Valor líquido</span>
                <span class="text-emerald-700 tabular-nums">R$ {{ number_format($valorLiquido, 2, ',', '.') }}</span>
            </div>
        </div>
    @endif

    {{-- Datas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="tx-competencia" class="label">Data de competência <span class="text-red-600">*</span></label>
            <input
                id="tx-competencia"
                type="date"
                wire:model="dataCompetencia"
                class="input @error('dataCompetencia') border-red-300 @enderror"
            >
            @error('dataCompetencia')
                <p class="field-error">{{ $message }}</p>
            @else
                <p class="hint">O mês em que o valor conta no resultado.</p>
            @enderror
        </div>
        <div>
            <label for="tx-pagamento" class="label">
                Data de pagamento @if ($status === 'pago') <span class="text-red-600">*</span> @endif
            </label>
            <input
                id="tx-pagamento"
                type="date"
                wire:model="dataPagamento"
                class="input @error('dataPagamento') border-red-300 @enderror"
            >
            @error('dataPagamento') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Situação --}}
    <div>
        <label for="tx-status" class="label">Situação <span class="text-red-600">*</span></label>
        <select id="tx-status" wire:model.live="status" class="input @error('status') border-red-300 @enderror">
            @foreach ($statusEnum as $st)
                <option value="{{ $st->value }}">{{ $st->label() }}</option>
            @endforeach
        </select>
        @error('status') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Repetição: só ao criar; depois é gerenciada na aba Recorrências --}}
    @if ($transacaoEditandoId === null)
        <div class="rounded-xl border border-stone-200 p-4 space-y-4">
            <div>
                <label for="tx-repetir" class="label">Repetir</label>
                <select id="tx-repetir" wire:model.live="recorrencia" class="input @error('recorrencia') border-red-300 @enderror">
                    @foreach ($recorrenciasEnum as $rec)
                        <option value="{{ $rec->value }}">{{ $rec->label() }}</option>
                    @endforeach
                </select>
                <p class="hint">Para contas fixas como aluguel, salários e internet. O sistema cria os próximos sozinho, no início de cada mês.</p>
                @error('recorrencia') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($recorrencia !== 'unica')
                <div>
                    <label for="tx-repetir-ate" class="label">Repetir até <span class="font-normal text-stone-500">(opcional)</span></label>
                    <input id="tx-repetir-ate" type="date" wire:model="recorrenciaAte" class="input @error('recorrenciaAte') border-red-300 @enderror">
                    <p class="hint">Deixe em branco para repetir até você encerrar.</p>
                    @error('recorrenciaAte') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <label class="flex min-h-[44px] items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="recorrenciaPago" class="mt-1 w-4 h-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                    <span class="text-sm text-stone-700">
                        Lançar os próximos já como pagos
                        <span class="block text-xs text-stone-500">Use para débito automático. Senão, eles entram como pendentes para você marcar.</span>
                    </span>
                </label>
            @endif
        </div>
    @elseif ($editandoRecorrente)
        <p class="rounded-xl bg-stone-50 px-4 py-3 text-sm text-stone-600">
            Este lançamento faz parte de uma recorrência. A alteração vale só para ele; para mudar os próximos, use
            <a href="{{ route('transacoes.recorrencias') }}" class="font-semibold text-rose-600 hover:underline">Recorrências</a>.
        </p>
    @endif

    {{-- Observações --}}
    <div>
        <label for="tx-obs" class="label">Observações</label>
        <textarea
            id="tx-obs"
            wire:model="observacoes"
            rows="3"
            placeholder="Ex.: Pago em duas vezes no balcão"
            class="input resize-none @error('observacoes') border-red-300 @enderror"
        ></textarea>
        @error('observacoes') <p class="field-error">{{ $message }}</p> @enderror
    </div>

</div>
