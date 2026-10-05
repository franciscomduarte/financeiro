<div
    x-data="{ flashSucesso: @entangle('flashSucesso'), flashErro: @entangle('flashErro') }"
    x-init="$watch('flashSucesso', v => { if (v) setTimeout(() => $wire.set('flashSucesso', null), 4000) })"
>
    @php
        $fecharIcone = 'M6 18L18 6M6 6l12 12';
        $temFiltro   = $busca !== '' || $filtroCategoria !== '' || $filtroStatus !== '';
    @endphp

    <x-ui.page-header titulo="Documentos" subtitulo="Guarde alvarás, licenças e certificados e saiba antes quando vão vencer.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalCategoria()" class="btn-secondary flex-1 sm:flex-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                Categorias
            </button>
            <button type="button" wire:click="abrirModalDocumento()" class="btn-primary flex-1 sm:flex-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo documento
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    <div class="space-y-6">

    {{-- Flash --}}
    @if ($flashSucesso)
        <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 py-1 pl-4 pr-1 text-sm text-red-800" role="alert">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <span class="py-2">{{ $flashErro }}</span>
            <button type="button" wire:click="$set('flashErro', null)" class="ml-auto inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-red-700 hover:bg-red-100" aria-label="Fechar aviso">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fecharIcone }}"/></svg>
            </button>
        </div>
    @endif

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Documentos</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalDocumentos }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Vigentes</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalVigentes }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Vencem em 60 dias</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $totalVencendo > 0 ? 'text-amber-700' : 'text-stone-900' }}">{{ $totalVencendo }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Vencidos</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $totalVencidos > 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $totalVencidos }}</p>
        </div>
    </div>

    {{-- Alerta --}}
    @if ($totalVencidos > 0 || $totalVencendo > 0)
        <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $totalVencidos > 0 ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <p>
                @if ($totalVencidos > 0)
                    <span class="font-semibold">{{ $totalVencidos }} {{ $totalVencidos === 1 ? 'documento vencido' : 'documentos vencidos' }}.</span>
                @endif
                @if ($totalVencendo > 0)
                    <span class="font-semibold">{{ $totalVencendo }} {{ $totalVencendo === 1 ? 'vence' : 'vencem' }} nos próximos 60 dias.</span>
                @endif
                Use “Renovar” para registrar a nova versão.
            </p>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="busca" type="search" aria-label="Buscar documento"
                    placeholder="Buscar por título, número, órgão ou responsável" class="input pl-10">
            </div>
            <select wire:model.live="filtroCategoria" aria-label="Filtrar por categoria" class="input sm:w-52">
                <option value="">Todas as categorias</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                @endforeach
            </select>
            <select wire:model.live="filtroStatus" aria-label="Filtrar por situação" class="input sm:w-48">
                <option value="">Todas as situações</option>
                <option value="vigente">Vigente</option>
                <option value="vencendo">Vencendo</option>
                <option value="vencido">Vencido</option>
                <option value="renovando">Em renovação</option>
                <option value="arquivado">Arquivado</option>
            </select>
        </div>
    </div>

    {{-- Lista de documentos --}}
    <div class="card overflow-hidden">
        @if ($documentos->isEmpty())
            @if ($temFiltro)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Confira a busca ou escolha outra categoria e situação."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            @else
                <x-ui.empty-state
                    titulo="Nenhum documento ainda"
                    texto="Cadastre alvarás, licenças e certificados da clínica. Avisamos você antes de cada um vencer."
                    icone="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z">
                    <button type="button" wire:click="abrirModalDocumento()" class="btn-primary">Cadastrar documento</button>
                </x-ui.empty-state>
            @endif
        @else
            {{-- Desktop: tabela --}}
            <table class="hidden md:table w-full table-fixed text-sm">
                <colgroup>
                    <col class="w-[34%]">
                    <col class="w-[16%]">
                    <col class="w-[16%]">
                    <col class="w-[12%]">
                    <col class="w-[22%]">
                </colgroup>
                <thead class="bg-stone-50">
                    <tr class="text-left text-xs font-medium text-stone-500">
                        <th class="px-5 py-3 font-medium">Documento</th>
                        <th class="px-5 py-3 font-medium">Categoria</th>
                        <th class="px-5 py-3 font-medium">Validade</th>
                        <th class="px-5 py-3 font-medium">Situação</th>
                        <th class="px-5 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($documentos as $doc)
                        @php
                            $dias = $doc->diasParaVencer();
                            $vencido = $doc->estaVencido();
                            $vencendo = $doc->estaVencendo();
                        @endphp
                        <tr wire:key="doc-{{ $doc->id }}" class="align-top transition-colors hover:bg-stone-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-stone-900">{{ $doc->titulo }}</p>
                                <div class="mt-0.5 space-y-0.5 text-xs text-stone-500">
                                    @if ($doc->numero_documento)
                                        <p>Nº {{ $doc->numero_documento }}</p>
                                    @endif
                                    @if ($doc->orgao_emissor)
                                        <p>{{ $doc->orgao_emissor }}</p>
                                    @endif
                                    @if ($doc->responsavel)
                                        <p>Responsável: {{ $doc->responsavel }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $doc->categoria->corClasses() }}">{{ $doc->categoria->nome }}</span>
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                @if ($doc->data_validade)
                                    <p class="font-medium {{ $vencido ? 'text-red-700' : ($vencendo ? 'text-amber-700' : 'text-stone-800') }}">
                                        {{ $doc->data_validade->format('d/m/Y') }}
                                    </p>
                                    @if ($vencido)
                                        <p class="text-xs font-medium text-red-700">Venceu há {{ abs($dias) }} {{ abs($dias) === 1 ? 'dia' : 'dias' }}</p>
                                    @elseif ($dias === 0)
                                        <p class="text-xs font-semibold text-amber-700">Vence hoje</p>
                                    @elseif ($vencendo)
                                        <p class="text-xs text-amber-700">Vence em {{ $dias }} {{ $dias === 1 ? 'dia' : 'dias' }}</p>
                                    @endif
                                @else
                                    <span class="text-xs text-stone-500">Sem validade</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $doc->statusCorClasses() }}">{{ $doc->statusDisplay() }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap items-center justify-end gap-1">
                                    @if ($doc->temArquivo())
                                        <a href="{{ route('documentos.download', $doc->id) }}" target="_blank" class="btn-ghost px-2.5 text-xs" title="Baixar arquivo" aria-label="Baixar arquivo de {{ $doc->titulo }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                        </a>
                                    @endif
                                    @if ($doc->status !== \App\Enums\StatusDocumento::Arquivado)
                                        <button type="button" wire:click="abrirModalRenovar('{{ $doc->id }}')" class="btn-ghost px-2.5 text-xs text-rose-700 hover:text-rose-800">
                                            Renovar
                                        </button>
                                    @endif
                                    <button type="button" wire:click="abrirModalHistorico('{{ $doc->id }}')" class="btn-ghost px-2.5 text-xs">
                                        Histórico
                                    </button>
                                    <button type="button" wire:click="abrirModalDocumento('{{ $doc->id }}')" class="btn-ghost px-2.5 text-xs">
                                        Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Celular: cartões --}}
            <ul class="md:hidden divide-y divide-stone-100">
                @foreach ($documentos as $doc)
                    @php
                        $dias = $doc->diasParaVencer();
                        $vencido = $doc->estaVencido();
                        $vencendo = $doc->estaVencendo();
                    @endphp
                    <li wire:key="doc-m-{{ $doc->id }}" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900">{{ $doc->titulo }}</p>
                                @if ($doc->numero_documento || $doc->orgao_emissor)
                                    <p class="mt-0.5 text-xs text-stone-500">
                                        {{ collect([$doc->numero_documento ? 'Nº ' . $doc->numero_documento : null, $doc->orgao_emissor])->filter()->join(' · ') }}
                                    </p>
                                @endif
                            </div>
                            <span class="badge shrink-0 {{ $doc->statusCorClasses() }}">{{ $doc->statusDisplay() }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                            <span class="badge {{ $doc->categoria->corClasses() }}">{{ $doc->categoria->nome }}</span>
                            @if ($doc->data_validade)
                                <span class="tabular-nums {{ $vencido ? 'font-medium text-red-700' : ($vencendo ? 'font-medium text-amber-700' : 'text-stone-500') }}">
                                    @if ($vencido)
                                        Venceu há {{ abs($dias) }} {{ abs($dias) === 1 ? 'dia' : 'dias' }}
                                    @elseif ($dias === 0)
                                        Vence hoje
                                    @elseif ($vencendo)
                                        Vence em {{ $dias }} {{ $dias === 1 ? 'dia' : 'dias' }}
                                    @else
                                        Válido até {{ $doc->data_validade->format('d/m/Y') }}
                                    @endif
                                </span>
                            @else
                                <span class="text-stone-500">Sem validade</span>
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($doc->status !== \App\Enums\StatusDocumento::Arquivado)
                                <button type="button" wire:click="abrirModalRenovar('{{ $doc->id }}')" class="btn-secondary flex-1 px-3 text-xs">Renovar</button>
                            @endif
                            <button type="button" wire:click="abrirModalDocumento('{{ $doc->id }}')" class="btn-secondary flex-1 px-3 text-xs">Editar</button>
                            <button type="button" wire:click="abrirModalHistorico('{{ $doc->id }}')" class="btn-ghost px-3 text-xs">Histórico</button>
                            @if ($doc->temArquivo())
                                <a href="{{ route('documentos.download', $doc->id) }}" target="_blank" class="btn-ghost px-3 text-xs" aria-label="Baixar arquivo">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($documentos->hasPages())
            <div class="border-t border-stone-100 px-4 py-3">
                {{ $documentos->links() }}
            </div>
        @endif
    </div>

    {{-- Categorias --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-stone-900">Categorias</h2>
            <button type="button" wire:click="abrirModalCategoria()" class="btn-ghost px-3 text-sm">+ Nova categoria</button>
        </div>
        @if ($categorias->isEmpty())
            <div class="card">
                <x-ui.empty-state
                    titulo="Nenhuma categoria ainda"
                    texto="Crie categorias como Alvarás ou Licenças sanitárias para organizar seus documentos."
                    icone="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z">
                    <button type="button" wire:click="abrirModalCategoria()" class="btn-primary">Criar categoria</button>
                </x-ui.empty-state>
            </div>
        @else
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($categorias as $cat)
                    <div wire:key="cat-{{ $cat->id }}" class="card flex items-center justify-between gap-2 py-1 pl-4 pr-1">
                        <div class="min-w-0 py-2">
                            <span class="badge {{ $cat->corClasses() }}">{{ $cat->nome }}</span>
                            <p class="mt-1 text-xs text-stone-500">
                                {{ $cat->requer_validade ? 'Avisa ' . $cat->alerta_dias_antes . ' dias antes' : 'Validade opcional' }}
                            </p>
                        </div>
                        <button type="button" wire:click="abrirModalCategoria('{{ $cat->id }}')" class="btn-ghost shrink-0 px-2.5" title="Editar categoria" aria-label="Editar categoria {{ $cat->nome }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    </div>

    {{-- ═══════════════ MODAL: CATEGORIA ═══════════════ --}}
    <div x-show="$wire.modalCategoria" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40" x-trap="$wire.modalCategoria">
        <div @click.stop class="w-full sm:max-w-md max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-surface shadow-xl" role="dialog" aria-modal="true" aria-labelledby="modal-cat-titulo">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                <h2 id="modal-cat-titulo" class="text-lg font-semibold text-stone-900">
                    {{ $categoriaEditandoId ? 'Editar categoria' : 'Nova categoria' }}
                </h2>
                <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fecharIcone }}"/></svg>
                </button>
            </div>
            <form wire:submit="salvarCategoria" class="space-y-4 px-5 py-5 sm:px-6">
                <div>
                    <label for="cat-nome" class="label">Nome</label>
                    <input id="cat-nome" wire:model="catNome" type="text" class="input" placeholder="Ex.: Licença sanitária">
                    @error('catNome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cat-descricao" class="label">Descrição <span class="font-normal text-stone-500">(opcional)</span></label>
                    <input id="cat-descricao" wire:model="catDescricao" type="text" class="input" placeholder="Ex.: Emitida pela vigilância sanitária">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="cat-cor" class="label">Cor</label>
                        <select id="cat-cor" wire:model="catCor" class="input">
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
                        <label for="cat-alerta" class="label">Avisar (dias antes)</label>
                        <input id="cat-alerta" wire:model="catAlertaDias" type="number" inputmode="numeric" min="1" max="365" class="input tabular-nums" placeholder="Ex.: 30">
                        @error('catAlertaDias') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label for="catRequerValidade" class="flex min-h-[44px] cursor-pointer items-center gap-3">
                    <input wire:model="catRequerValidade" type="checkbox" id="catRequerValidade" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                    <span class="text-sm text-stone-700">Exigir data de validade</span>
                </label>
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="salvarCategoria">
                        <span wire:loading.remove wire:target="salvarCategoria">{{ $categoriaEditandoId ? 'Salvar alterações' : 'Criar categoria' }}</span>
                        <span wire:loading wire:target="salvarCategoria">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: DOCUMENTO ═══════════════ --}}
    <div x-show="$wire.modalDocumento" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40" x-trap="$wire.modalDocumento">
        <div @click.stop class="w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-surface shadow-xl" role="dialog" aria-modal="true" aria-labelledby="modal-doc-titulo">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-stone-100 bg-surface px-5 py-4 sm:px-6">
                <h2 id="modal-doc-titulo" class="text-lg font-semibold text-stone-900">
                    {{ $documentoEditandoId ? 'Editar documento' : 'Novo documento' }}
                </h2>
                <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fecharIcone }}"/></svg>
                </button>
            </div>
            <form wire:submit="salvarDocumento" class="space-y-4 px-5 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="doc-titulo" class="label">Título</label>
                        <input id="doc-titulo" wire:model="docTitulo" type="text" class="input" placeholder="Ex.: Alvará de funcionamento 2026">
                        @error('docTitulo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="doc-categoria" class="label">Categoria</label>
                        <select id="doc-categoria" wire:model="docCategoriaId" class="input">
                            <option value="">Selecione</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                            @endforeach
                        </select>
                        @error('docCategoriaId') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="doc-status" class="label">Situação</label>
                        <select id="doc-status" wire:model="docStatus" class="input">
                            @foreach ($statusOpcoes as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="doc-numero" class="label">Número do documento</label>
                        <input id="doc-numero" wire:model="docNumero" type="text" class="input" placeholder="Ex.: 2026/001234">
                    </div>
                    <div>
                        <label for="doc-orgao" class="label">Órgão emissor</label>
                        <input id="doc-orgao" wire:model="docOrgao" type="text" class="input" placeholder="Ex.: Vigilância Sanitária">
                    </div>
                    <div>
                        <label for="doc-responsavel" class="label">Responsável</label>
                        <input id="doc-responsavel" wire:model="docResponsavel" type="text" class="input" placeholder="Ex.: Maria Silva">
                    </div>
                    <div>
                        <label for="doc-emissao" class="label">Data de emissão</label>
                        <input id="doc-emissao" wire:model="docDataEmissao" type="date" class="input">
                    </div>
                    <div>
                        <label for="doc-validade" class="label">Data de validade</label>
                        <input id="doc-validade" wire:model="docDataValidade" type="date" class="input">
                    </div>
                    <div>
                        <label for="doc-alerta" class="label">Avisar antes (dias)</label>
                        <input id="doc-alerta" wire:model="docAlertaDias" type="number" inputmode="numeric" min="1" max="365" placeholder="Padrão da categoria" class="input tabular-nums">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="doc-arquivo" class="label">Arquivo</label>
                        <input id="doc-arquivo" wire:model="docArquivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx"
                            class="block w-full text-sm text-stone-600 file:mr-3 file:min-h-[44px] file:cursor-pointer file:rounded-xl file:border-0 file:bg-rose-50 file:px-4 file:text-sm file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                        <p class="hint">PDF, imagem ou Word, até 100 MB.</p>
                        <div wire:loading wire:target="docArquivo" class="hint">Enviando…</div>
                        @error('docArquivo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="doc-obs" class="label">Observações</label>
                        <textarea id="doc-obs" wire:model="docObservacoes" rows="2" class="input"></textarea>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="salvarDocumento">
                        <span wire:loading.remove wire:target="salvarDocumento">{{ $documentoEditandoId ? 'Salvar alterações' : 'Salvar documento' }}</span>
                        <span wire:loading wire:target="salvarDocumento">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: RENOVAR ═══════════════ --}}
    <div x-show="$wire.modalRenovar" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40" x-trap="$wire.modalRenovar">
        <div @click.stop class="w-full sm:max-w-lg max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-surface shadow-xl" role="dialog" aria-modal="true" aria-labelledby="modal-ren-titulo">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                <h2 id="modal-ren-titulo" class="text-lg font-semibold text-stone-900">Renovar documento</h2>
                <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fecharIcone }}"/></svg>
                </button>
            </div>
            <form wire:submit="renovar" class="space-y-4 px-5 py-5 sm:px-6">
                <p class="rounded-xl bg-stone-50 px-3 py-2.5 text-sm text-stone-600">A versão atual vai para o histórico, e você pode consultá-la quando quiser.</p>
                <div>
                    <label for="ren-numero" class="label">Novo número do documento <span class="font-normal text-stone-500">(opcional)</span></label>
                    <input id="ren-numero" wire:model="renNumero" type="text" class="input" placeholder="Ex.: 2027/001234">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="ren-emissao" class="label">Data de emissão</label>
                        <input id="ren-emissao" wire:model="renDataEmissao" type="date" class="input">
                        @error('renDataEmissao') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ren-validade" class="label">Nova validade</label>
                        <input id="ren-validade" wire:model="renDataValidade" type="date" class="input">
                        @error('renDataValidade') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="ren-arquivo" class="label">Arquivo renovado</label>
                    <input id="ren-arquivo" wire:model="renArquivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx"
                        class="block w-full text-sm text-stone-600 file:mr-3 file:min-h-[44px] file:cursor-pointer file:rounded-xl file:border-0 file:bg-rose-50 file:px-4 file:text-sm file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    <p class="hint">PDF, imagem ou Word, até 100 MB.</p>
                    <div wire:loading wire:target="renArquivo" class="hint">Enviando…</div>
                </div>
                <div>
                    <label for="ren-obs" class="label">Observações</label>
                    <textarea id="ren-obs" wire:model="renObservacoes" rows="2" class="input"></textarea>
                </div>
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="renovar">
                        <span wire:loading.remove wire:target="renovar">Renovar documento</span>
                        <span wire:loading wire:target="renovar">Renovando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: HISTÓRICO ═══════════════ --}}
    <div x-show="$wire.modalHistorico" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40" x-trap="$wire.modalHistorico">
        <div @click.stop class="flex w-full sm:max-w-2xl max-h-[92vh] sm:max-h-[80vh] flex-col rounded-t-2xl sm:rounded-2xl bg-surface shadow-xl" role="dialog" aria-modal="true" aria-labelledby="modal-hist-titulo">
            <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <h2 id="modal-hist-titulo" class="text-lg font-semibold text-stone-900">Histórico de versões</h2>
                    @if ($documentoHistorico)
                        <p class="truncate text-sm text-stone-500">{{ $documentoHistorico->titulo }}</p>
                    @endif
                </div>
                <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 shrink-0 px-2.5" aria-label="Fechar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fecharIcone }}"/></svg>
                </button>
            </div>
            <div class="overflow-y-auto px-5 py-4 sm:px-6">
                @if ($versoes->isEmpty())
                    <x-ui.empty-state
                        titulo="Nenhuma versão anterior"
                        texto="Quando você renovar este documento, a versão atual fica guardada aqui."
                        icone="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                    <ul class="space-y-3">
                        @foreach ($versoes as $v)
                            <li wire:key="versao-{{ $v->id }}" class="rounded-xl border border-stone-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-stone-900 tabular-nums">
                                            Versão de {{ $v->created_at->format('d/m/Y H:i') }}
                                        </p>
                                        @if ($v->numero_documento)
                                            <p class="mt-0.5 text-xs text-stone-500">Nº {{ $v->numero_documento }}</p>
                                        @endif
                                        <div class="mt-0.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-stone-500 tabular-nums">
                                            @if ($v->data_emissao)
                                                <span>Emitido em {{ $v->data_emissao->format('d/m/Y') }}</span>
                                            @endif
                                            @if ($v->data_validade)
                                                <span>Válido até {{ $v->data_validade->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                        @if ($v->observacoes)
                                            <p class="mt-1 text-xs italic text-stone-500">{{ $v->observacoes }}</p>
                                        @endif
                                    </div>
                                    @if ($v->temArquivo())
                                        <a href="{{ route('documentos.versao.download', $v->id) }}" target="_blank" class="btn-secondary shrink-0 px-3 text-xs">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            Baixar
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="border-t border-stone-100 px-5 py-4 sm:px-6">
                <button type="button" wire:click="fecharModais" class="btn-secondary w-full">Fechar</button>
            </div>
        </div>
    </div>
</div>
