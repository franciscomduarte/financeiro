{{-- Cartão do lead no funil. Parâmetro: $l --}}
<button type="button" wire:click="abrir('{{ $l->id }}')" class="card block w-full p-3 text-left transition hover:border-rose-200 hover:shadow-sm">
    <div class="flex items-start justify-between gap-2">
        <p class="truncate text-sm font-medium text-stone-900">{{ $l->nome }}</p>
        <span class="shrink-0 text-xs text-stone-400">{{ $l->origem->label() }}</span>
    </div>
    @if ($l->procedimento || $l->interesse)
        <p class="mt-0.5 truncate text-xs text-stone-500">{{ $l->procedimento?->nome ?? $l->interesse }}</p>
    @endif
    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
        @if ($l->semResposta())
            <span class="badge bg-red-50 text-red-700">Sem resposta há {{ $l->created_at->diffForHumans(null, true) }}</span>
        @elseif ($l->contatoAtrasado())
            <span class="badge bg-amber-50 text-amber-700">Contato atrasado</span>
        @elseif ($l->proximo_contato_em && $l->etapa->aberta())
            <span class="badge bg-sky-50 text-sky-700">Contatar {{ $l->proximo_contato_em->isToday() ? 'hoje ' . $l->proximo_contato_em->format('H:i') : $l->proximo_contato_em->format('d/m') }}</span>
        @endif
        @if ($l->etapa === \App\Enums\EtapaLead::Perdido && $l->motivo_perda)
            <span class="text-stone-400">{{ $l->motivo_perda }}</span>
        @endif
        @if ($l->responsavel)
            <span class="ml-auto text-stone-400">{{ explode(' ', $l->responsavel->name)[0] }}</span>
        @endif
    </div>
</button>
