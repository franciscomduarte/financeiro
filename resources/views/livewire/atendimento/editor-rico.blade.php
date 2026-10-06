{{-- Editor de texto (Tiptap). Parâmetros: $modeloId, $campoId, $valor, $rotulo --}}
@php
    $botoes = [
        ['negrito', 'ativo("bold")', 'Negrito', '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.744h-.753v8.25h7.125a4.125 4.125 0 000-8.25H6.75zm0 0v.38m0 16.122h6.747a4.5 4.5 0 000-9.001h-7.5v9h.753zm0 0v-.37m0-15.751h6a3.75 3.75 0 110 7.5h-6m0-7.5v7.5m0 0v8.25m0-8.25h6.375a4.125 4.125 0 010 8.25H6.75m.747-15.38h4.875a3.375 3.375 0 010 6.75H7.497v-6.75zm0 7.5h5.25a3.75 3.75 0 010 7.5h-5.25v-7.5z"/>'],
        ['italico', 'ativo("italic")', 'Itálico', '<path stroke-linecap="round" stroke-linejoin="round" d="M5.248 20.246H9.05m0 0h3.696m-3.696 0l5.893-16.502m0 0h-3.697m3.697 0h3.803"/>'],
        ['sublinhado', 'ativo("underline")', 'Sublinhado', '<path stroke-linecap="round" stroke-linejoin="round" d="M17.995 3.744v7.5a6 6 0 11-12 0v-7.5m-2.25 16.502h16.5"/>'],
        ['riscado', 'ativo("strike")', 'Riscado', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12a8.912 8.912 0 01-.318-.079c-1.585-.424-2.904-1.247-3.76-2.236-.873-1.009-1.265-2.19-.968-3.301.59-2.2 3.663-3.29 6.863-2.432A8.186 8.186 0 0116.5 5.21M6.42 17.81c.857.99 2.176 1.812 3.761 2.237 3.2.858 6.274-.23 6.863-2.431.233-.868.044-1.779-.465-2.617M3.75 12h16.5"/>'],
        ['marcador', 'ativo("highlight")', 'Marca-texto', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>'],
        ['lista', 'ativo("bulletList")', 'Lista', '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>'],
        ['numerada', 'ativo("orderedList")', 'Lista numerada', '<path stroke-linecap="round" stroke-linejoin="round" d="M8.242 5.992h12m-12 6.003H20.24m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H5.24m-1.92 2.577a1.125 1.125 0 111.591 1.59l-1.83 1.83h2.16M2.99 15.745h1.125a1.125 1.125 0 010 2.25H3.74m0-.002h.375a1.125 1.125 0 010 2.25H2.99"/>'],
    ];
    $alinhamentos = ['left' => 'M3.75 6.75h16.5M3.75 12h10.5m-10.5 5.25h16.5', 'center' => 'M3.75 6.75h16.5M6.75 12h10.5m-13.5 5.25h16.5', 'right' => 'M3.75 6.75h16.5M9.75 12h10.5m-16.5 5.25h16.5', 'justify' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5'];
@endphp
<div wire:ignore wire:key="editor-{{ $modeloId }}-{{ $campoId }}"
     x-data="editorRico({ valor: @js($valor), editavel: true, rotulo: @js($rotulo), salvar: (html) => $wire.salvarTextoRico(@js($modeloId), @js($campoId), html) })"
     class="overflow-hidden rounded-xl border border-stone-200 bg-surface focus-within:border-rose-300 focus-within:ring-4 focus-within:ring-rose-100">
    <div class="flex flex-wrap items-center gap-0.5 border-b border-stone-100 px-1.5 py-1" role="toolbar" aria-label="Formatação de {{ $rotulo }}">
        @foreach ($botoes as [$acao, $estado, $titulo, $icone])
            <button type="button" x-on:click="cmd('{{ $acao }}')" x-bind:class="{{ $estado }} ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-100'"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg" title="{{ $titulo }}" aria-label="{{ $titulo }}">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">{!! $icone !!}</svg>
            </button>
        @endforeach
        <label class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg text-stone-600 hover:bg-stone-100" title="Cor do texto">
            <span class="text-sm font-bold underline decoration-rose-500 decoration-2 underline-offset-2" aria-hidden="true">A</span>
            <input type="color" class="sr-only" aria-label="Cor do texto" x-on:input="cmd('cor', $event.target.value)">
        </label>
        <span class="mx-1 h-6 w-px bg-stone-200" aria-hidden="true"></span>
        @foreach ($alinhamentos as $lado => $icone)
            <button type="button" x-on:click="cmd('alinhar', '{{ $lado }}')" x-bind:class="alinhado('{{ $lado }}') ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-100'"
                    class="hidden h-10 w-10 items-center justify-center rounded-lg sm:inline-flex" title="Alinhar" aria-label="Alinhar ({{ $lado }})">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}"/></svg>
            </button>
        @endforeach
        <span class="mx-1 h-6 w-px bg-stone-200" aria-hidden="true"></span>
        <button type="button" x-on:click="cmd('limpar')" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-stone-600 hover:bg-stone-100" title="Limpar formatação" aria-label="Limpar formatação">
            <span class="text-sm italic" aria-hidden="true">T<sub>x</sub></span>
        </button>
        <button type="button" x-on:click="cmd('desfazer')" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-stone-600 hover:bg-stone-100" title="Desfazer" aria-label="Desfazer">
            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
        </button>
        <button type="button" x-on:click="cmd('refazer')" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-stone-600 hover:bg-stone-100" title="Refazer" aria-label="Refazer">
            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3"/></svg>
        </button>
        <span class="ml-auto pr-2 text-xs text-stone-400" x-show="salvando" x-cloak>salvando…</span>
    </div>
    <div x-ref="area"></div>
</div>
