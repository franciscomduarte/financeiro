<div class="space-y-4">

    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V5z" clip-rule="evenodd"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Configurações — Agenda</h1>
        <p class="text-sm text-gray-500">Gerencie profissionais, grades horárias e procedimentos</p>
    </div>

    {{-- Abas --}}
    <div class="flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1">
        <button wire:click="$set('aba', 'profissionais')"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors
                    {{ $aba === 'profissionais' ? 'bg-white text-violet-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
            Profissionais
        </button>
        <button wire:click="$set('aba', 'procedimentos')"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors
                    {{ $aba === 'procedimentos' ? 'bg-white text-violet-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
            Procedimentos
        </button>
    </div>

    {{-- ════════════════════════════ ABA: PROFISSIONAIS ════════════════════════════ --}}
    @if ($aba === 'profissionais')
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Profissionais</h2>
            <button wire:click="abrirModalNovoProfissional"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-violet-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Novo
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 hidden sm:table-cell">E-mail</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 hidden md:table-cell">Telefone</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($profissionais as $p)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-900">
                                    <span class="h-3 w-3 rounded-full flex-shrink-0" style="background-color: {{ $p->cor_agenda }}"></span>
                                    {{ $p->nome }}
                                </span>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell text-sm text-gray-600">{{ $p->email }}</td>
                            <td class="px-4 py-3 hidden md:table-cell text-sm text-gray-600">{{ $p->telefone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $p->ativo ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $p->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="abrirModalGrade('{{ $p->id }}')"
                                            title="Grade horária"
                                            class="rounded-md p-1.5 text-violet-600 hover:bg-violet-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </button>
                                    <button wire:click="abrirModalEditarProfissional('{{ $p->id }}')"
                                            title="Editar"
                                            class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                                Nenhum profissional cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ════════════════════════════ ABA: PROCEDIMENTOS ════════════════════════════ --}}
    @if ($aba === 'procedimentos')
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Procedimentos</h2>
            <button wire:click="abrirModalNovoProcedimento"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-violet-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Novo
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 hidden sm:table-cell">Duração</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 hidden sm:table-cell">Valor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($procedimentos as $proc)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $proc->nome }}</td>
                            <td class="px-4 py-3 hidden sm:table-cell text-sm text-gray-600">{{ $proc->duracao_minutos }} min</td>
                            <td class="px-4 py-3 hidden sm:table-cell text-sm text-gray-900">R$ {{ number_format($proc->valor, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $proc->ativo ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $proc->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="abrirModalEditarProcedimento({{ $proc->id }})"
                                        class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                                Nenhum procedimento cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═════════════════ Modal: Profissional (criar/editar) ═════════════════ --}}
    @if ($modalProfissional)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharModalProfissional"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">
                    {{ $profissionalEditandoId ? 'Editar Profissional' : 'Novo Profissional' }}
                </h2>
                <button wire:click="fecharModalProfissional" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="profNome"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('profNome') border-red-300 @enderror">
                    @error('profNome') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">E-mail <span class="text-red-500">*</span></label>
                    <input type="email" wire:model="profEmail"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('profEmail') border-red-300 @enderror">
                    @error('profEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="tel" wire:model="profTelefone"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Cor na Agenda</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model="profCor" class="h-9 w-16 cursor-pointer rounded-lg border-gray-300">
                        <span class="text-sm text-gray-500">{{ $profCor }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="profAtivo" wire:model="profAtivo" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    <label for="profAtivo" class="text-sm text-gray-700">Ativo</label>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharModalProfissional"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="salvarProfissional" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading wire:target="salvarProfissional">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═════════════════════ Modal: Grade Horária ═════════════════════════── --}}
    @if ($modalGrade)
    @php
        $diasNomes = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharModalGrade"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Grade Horária</h2>
                    <p class="text-xs text-gray-500">{{ $gradeEditandoNome }}</p>
                </div>
                <button wire:click="fecharModalGrade" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach ($grade as $dia => $config)
                        <div class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="w-8 flex-shrink-0">
                                <input type="checkbox"
                                       wire:model="grade.{{ $dia }}.ativo"
                                       id="grade_ativo_{{ $dia }}"
                                       class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                            </div>
                            <label for="grade_ativo_{{ $dia }}"
                                   class="w-8 flex-shrink-0 text-sm font-medium {{ $config['ativo'] ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $diasNomes[$dia] }}
                            </label>
                            @if ($config['ativo'])
                                <input type="time" wire:model="grade.{{ $dia }}.hora_inicio"
                                       class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                                <span class="text-xs text-gray-400">até</span>
                                <input type="time" wire:model="grade.{{ $dia }}.hora_fim"
                                       class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                            @else
                                <span class="flex-1 text-sm text-gray-400">Sem atendimento</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharModalGrade"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="salvarGrade" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading wire:target="salvarGrade">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar Grade
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═════════════════ Modal: Procedimento (criar/editar) ═════════════════ --}}
    @if ($modalProcedimento)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharModalProcedimento"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">
                    {{ $procedimentoEditandoId ? 'Editar Procedimento' : 'Novo Procedimento' }}
                </h2>
                <button wire:click="fecharModalProcedimento" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="procNome"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('procNome') border-red-300 @enderror">
                    @error('procNome') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Descrição</label>
                    <textarea wire:model="procDescricao" rows="2"
                              class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Duração (min) <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="procDuracao" min="15" max="480" step="15"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('procDuracao') border-red-300 @enderror">
                        @error('procDuracao') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Valor (R$) <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="procValor" min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('procValor') border-red-300 @enderror">
                        @error('procValor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="procAtivo" wire:model="procAtivo" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    <label for="procAtivo" class="text-sm text-gray-700">Ativo</label>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharModalProcedimento"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="salvarProcedimento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading wire:target="salvarProcedimento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
