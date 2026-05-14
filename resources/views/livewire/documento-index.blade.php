<div
    x-data="{ flashSucesso: @entangle('flashSucesso'), flashErro: @entangle('flashErro') }"
    x-init="$watch('flashSucesso', v => { if (v) setTimeout(() => $wire.set('flashSucesso', null), 4000) })"
    class="space-y-6"
>
    {{-- Flash --}}
    @if ($flashSucesso)
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
            {{ $flashErro }}
            <button wire:click="$set('flashErro', null)" class="ml-auto text-red-600 hover:text-red-800">&times;</button>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Documentos</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gestão de documentos, licenças e certificados da empresa</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="abrirModalCategoria()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                Categorias
            </button>
            <button wire:click="abrirModalDocumento()" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo Documento
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl bg-white border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
            <p class="text-3xl font-bold text-slate-800 mt-1">{{ $totalDocumentos }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Vigentes</p>
            <p class="text-3xl font-bold text-green-700 mt-1">{{ $totalVigentes }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Vencendo em 60d</p>
            <p class="text-3xl font-bold {{ $totalVencendo > 0 ? 'text-amber-600' : 'text-slate-800' }} mt-1">{{ $totalVencendo }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Vencidos</p>
            <p class="text-3xl font-bold {{ $totalVencidos > 0 ? 'text-red-600' : 'text-slate-800' }} mt-1">{{ $totalVencidos }}</p>
        </div>
    </div>

    {{-- Alertas --}}
    @if ($totalVencidos > 0 || $totalVencendo > 0)
        <div class="rounded-lg border {{ $totalVencidos > 0 ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 shrink-0 {{ $totalVencidos > 0 ? 'text-red-500' : 'text-amber-500' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <div class="{{ $totalVencidos > 0 ? 'text-red-800' : 'text-amber-800' }}">
                @if ($totalVencidos > 0)
                    <span class="font-semibold">{{ $totalVencidos }} documento(s) vencido(s).</span>
                @endif
                @if ($totalVencendo > 0)
                    <span class="font-semibold">{{ $totalVencendo }} documento(s) vencendo nos próximos 60 dias.</span>
                @endif
                Providencie a renovação.
            </div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="busca" type="text" placeholder="Buscar por título, número, órgão, responsável…"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
        </div>
        <select wire:model.live="filtroCategoria" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            <option value="">Todas as categorias</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
            @endforeach
        </select>
        <select wire:model.live="filtroStatus" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            <option value="">Todos os status</option>
            <option value="vigente">Vigente</option>
            <option value="vencendo">Vencendo</option>
            <option value="vencido">Vencido</option>
            <option value="renovando">Em Renovação</option>
            <option value="arquivado">Arquivado</option>
        </select>
    </div>

    {{-- Tabela de documentos --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed divide-y divide-slate-200 text-sm">
                <colgroup>
                    <col class="w-[38%]">
                    <col class="w-[18%]">
                    <col class="w-[18%]">
                    <col class="w-[12%]">
                    <col class="w-[14%]">
                </colgroup>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Documento</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Categoria</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Validade</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($documentos as $doc)
                        @php
                            $dias = $doc->diasParaVencer();
                            $vencido = $doc->estaVencido();
                            $vencendo = $doc->estaVencendo();
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $vencido ? 'bg-red-50/30' : ($vencendo ? 'bg-amber-50/30' : '') }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $doc->titulo }}</div>
                                @if ($doc->numero_documento)
                                    <div class="text-xs text-slate-400 mt-0.5">Nº {{ $doc->numero_documento }}</div>
                                @endif
                                @if ($doc->orgao_emissor)
                                    <div class="text-xs text-slate-400">{{ $doc->orgao_emissor }}</div>
                                @endif
                                @if ($doc->responsavel)
                                    <div class="text-xs text-slate-400">Resp: {{ $doc->responsavel }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->categoria->corClasses() }}">
                                    {{ $doc->categoria->nome }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($doc->data_validade)
                                    <div class="font-medium {{ $vencido ? 'text-red-700' : ($vencendo ? 'text-amber-700' : 'text-slate-800') }}">
                                        {{ $doc->data_validade->format('d/m/Y') }}
                                    </div>
                                    @if ($vencido)
                                        <div class="text-xs text-red-600 font-medium">Vencido há {{ abs($dias) }}d</div>
                                    @elseif ($dias === 0)
                                        <div class="text-xs text-amber-600 font-semibold">Vence hoje!</div>
                                    @elseif ($vencendo)
                                        <div class="text-xs text-amber-600">Vence em {{ $dias }}d</div>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">Sem validade</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->statusCorClasses() }}">
                                    {{ $doc->statusDisplay() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($doc->temArquivo())
                                        <a href="{{ route('documentos.download', $doc->id) }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            Arquivo
                                        </a>
                                    @endif
                                    @if ($doc->status !== \App\Enums\StatusDocumento::Arquivado)
                                        <button wire:click="abrirModalRenovar('{{ $doc->id }}')" class="text-xs text-green-700 hover:text-green-900 font-medium">
                                            Renovar
                                        </button>
                                    @endif
                                    <button wire:click="abrirModalHistorico('{{ $doc->id }}')" class="text-xs text-slate-500 hover:text-slate-700 font-medium">
                                        Histórico
                                    </button>
                                    <button wire:click="abrirModalDocumento('{{ $doc->id }}')" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                Nenhum documento encontrado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($documentos->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $documentos->links() }}
            </div>
        @endif
    </div>

    {{-- Categorias grid --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-600">Categorias cadastradas</h2>
            <button wire:click="abrirModalCategoria()" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Nova categoria</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            @foreach ($categorias as $cat)
                <div class="flex items-start justify-between rounded-xl px-3 py-2.5 {{ $cat->corClasses() }}">
                    <div class="min-w-0 text-xs">
                        <p class="font-semibold leading-snug">{{ $cat->nome }}</p>
                        <p class="mt-0.5 opacity-60 leading-tight">
                            {{ $cat->requer_validade ? 'Alerta ' . $cat->alerta_dias_antes . 'd antes' : 'Sem validade obrigatória' }}
                        </p>
                    </div>
                    <button wire:click="abrirModalCategoria('{{ $cat->id }}')"
                        class="opacity-40 hover:opacity-80 ml-2 shrink-0 mt-0.5 transition"
                        title="Editar categoria">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ MODAL: CATEGORIA ═══════════════ --}}
    <div x-show="$wire.modalCategoria" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-trap="$wire.modalCategoria">
        <div @click.stop class="w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ $categoriaEditandoId ? 'Editar Categoria' : 'Nova Categoria' }}
                </h2>
                <button wire:click="fecharModais" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <form wire:submit="salvarCategoria" class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nome *</label>
                    <input wire:model="catNome" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('catNome') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                    <input wire:model="catDescricao" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cor</label>
                        <select wire:model="catCor" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="slate">Cinza</option>
                            <option value="red">Vermelho</option>
                            <option value="orange">Laranja</option>
                            <option value="amber">Âmbar</option>
                            <option value="green">Verde</option>
                            <option value="blue">Azul</option>
                            <option value="indigo">Índigo</option>
                            <option value="purple">Roxo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alertar (dias antes)</label>
                        <input wire:model="catAlertaDias" type="number" min="1" max="365" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('catAlertaDias') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="catRequerValidade" type="checkbox" id="catRequerValidade" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="catRequerValidade" class="text-sm text-slate-700">Requer data de validade</label>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="fecharModais" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        <span wire:loading.remove wire:target="salvarCategoria">Salvar</span>
                        <span wire:loading wire:target="salvarCategoria">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: DOCUMENTO ═══════════════ --}}
    <div x-show="$wire.modalDocumento" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-trap="$wire.modalDocumento">
        <div @click.stop class="w-full max-w-2xl rounded-2xl bg-white shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ $documentoEditandoId ? 'Editar Documento' : 'Novo Documento' }}
                </h2>
                <button wire:click="fecharModais" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <form wire:submit="salvarDocumento" class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Título *</label>
                        <input wire:model="docTitulo" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('docTitulo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Categoria *</label>
                        <select wire:model="docCategoriaId" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Selecione…</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                            @endforeach
                        </select>
                        @error('docCategoriaId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
                        <select wire:model="docStatus" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($statusOpcoes as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Número do Documento</label>
                        <input wire:model="docNumero" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Órgão Emissor</label>
                        <input wire:model="docOrgao" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Responsável</label>
                        <input wire:model="docResponsavel" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Data de Emissão</label>
                        <input wire:model="docDataEmissao" type="date" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Data de Validade</label>
                        <input wire:model="docDataValidade" type="date" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alertar antes (dias)</label>
                        <input wire:model="docAlertaDias" type="number" min="1" max="365" placeholder="Padrão da categoria"
                            class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Arquivo (PDF, imagem)</label>
                        <input wire:model="docArquivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"
                            class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div wire:loading wire:target="docArquivo" class="text-xs text-slate-400 mt-1">Enviando…</div>
                        @error('docArquivo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                        <textarea wire:model="docObservacoes" rows="2" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="fecharModais" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        <span wire:loading.remove wire:target="salvarDocumento">Salvar</span>
                        <span wire:loading wire:target="salvarDocumento">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: RENOVAR ═══════════════ --}}
    <div x-show="$wire.modalRenovar" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-trap="$wire.modalRenovar">
        <div @click.stop class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800">Renovar Documento</h2>
                <button wire:click="fecharModais" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <div class="px-6 py-3 bg-blue-50 border-b border-blue-100">
                <p class="text-xs text-blue-700">A versão atual será arquivada no histórico antes de salvar a renovação.</p>
            </div>
            <form wire:submit="renovar" class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número do Documento (novo)</label>
                    <input wire:model="renNumero" type="text" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Data de Emissão *</label>
                        <input wire:model="renDataEmissao" type="date" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('renDataEmissao') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nova Validade</label>
                        <input wire:model="renDataValidade" type="date" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('renDataValidade') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Arquivo renovado (PDF, imagem)</label>
                    <input wire:model="renArquivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <div wire:loading wire:target="renArquivo" class="text-xs text-slate-400 mt-1">Enviando…</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                    <textarea wire:model="renObservacoes" rows="2" class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="fecharModais" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="flex-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                        <span wire:loading.remove wire:target="renovar">Renovar</span>
                        <span wire:loading wire:target="renovar">Renovando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: HISTÓRICO ═══════════════ --}}
    <div x-show="$wire.modalHistorico" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-trap="$wire.modalHistorico">
        <div @click.stop class="w-full max-w-2xl rounded-2xl bg-white shadow-xl max-h-[80vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Histórico de Versões</h2>
                    @if ($documentoHistorico)
                        <p class="text-sm text-slate-500">{{ $documentoHistorico->titulo }}</p>
                    @endif
                </div>
                <button wire:click="fecharModais" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <div class="px-6 py-4">
                @if ($versoes->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-8">Nenhuma versão anterior registrada.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($versoes as $v)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-medium text-slate-700">
                                            Versão de {{ $v->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        @if ($v->numero_documento)
                                            <div class="text-xs text-slate-500 mt-0.5">Nº {{ $v->numero_documento }}</div>
                                        @endif
                                        <div class="text-xs text-slate-500 mt-0.5 flex gap-4">
                                            @if ($v->data_emissao)
                                                <span>Emitido: {{ $v->data_emissao->format('d/m/Y') }}</span>
                                            @endif
                                            @if ($v->data_validade)
                                                <span>Válido até: {{ $v->data_validade->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                        @if ($v->observacoes)
                                            <div class="text-xs text-slate-400 mt-1 italic">{{ $v->observacoes }}</div>
                                        @endif
                                    </div>
                                    @if ($v->temArquivo())
                                        <a href="{{ route('documentos.versao.download', $v->id) }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            Baixar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                <button wire:click="fecharModais" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Fechar</button>
            </div>
        </div>
    </div>
</div>
