@php
    $fuso = config('clinica.fuso_horario');
    $quando = function ($data) use ($fuso) {
        $d = $data->copy()->timezone($fuso);
        return $d->isToday() ? 'hoje ' . $d->format('H:i') : ($d->isYesterday() ? 'ontem ' . $d->format('H:i') : $d->format('d/m H:i'));
    };
    $p = $this->paciente;
@endphp
<section class="rounded-xl border border-stone-200" aria-label="Conversa no WhatsApp" wire:poll.15s="atualizar">
    <div class="flex items-center gap-2 border-b border-stone-100 px-3 py-2">
        <x-icone.whatsapp class="h-4 w-4 text-emerald-600" />
        <h3 class="flex-1 text-sm font-semibold text-stone-900">Conversa no WhatsApp</h3>
    </div>
    <div class="max-h-72 space-y-2 overflow-y-auto bg-stone-50/60 px-3 py-3" wire:key="conversa-paciente-{{ $p->id }}-{{ $this->mensagens->count() }}" x-data x-init="$el.scrollTop = $el.scrollHeight">
        @forelse ($this->mensagens as $m)
            <div class="flex {{ $m->enviada ? 'justify-end' : 'justify-start' }}" wire:key="pm-{{ $m->id }}">
                <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm {{ $m->enviada ? 'rounded-br-sm bg-emerald-100 text-emerald-950' : 'rounded-bl-sm bg-surface text-stone-800' }}">
                    <p class="whitespace-pre-line break-words">{{ $m->texto }}</p>
                    <p class="mt-0.5 text-right text-[11px] {{ $m->enviada ? 'text-emerald-800/70' : 'text-stone-400' }}">
                        {{ $m->enviada ? ($m->autor ? explode(' ', $m->autor->name)[0] . ' · ' : 'Celular da clínica · ') : '' }}{{ $quando($m->created_at) }}
                    </p>
                </div>
            </div>
        @empty
            <p class="py-4 text-center text-xs text-stone-400">Nenhuma mensagem ainda. O que o paciente mandar para o WhatsApp da clínica aparece aqui.</p>
        @endforelse
    </div>
    @if (! $podeEnviar)
        {{-- somente leitura: só vê a conversa --}}
    @elseif (! $conectado)
        <p class="hint border-t border-stone-100 px-3 py-2">Para responder por aqui, conecte o WhatsApp da clínica em Dados da clínica.</p>
    @elseif (! \App\Support\Telefone::nacional($p->telefone) || $p->anonimizado_em)
        <p class="hint border-t border-stone-100 px-3 py-2">Cadastre um celular com DDD na ficha para responder por aqui.</p>
    @else
        <form wire:submit="enviar" class="flex items-end gap-2 border-t border-stone-100 p-2">
            <textarea wire:model="resposta" rows="2" maxlength="2000" class="input min-h-11 flex-1 resize-none" aria-label="Mensagem para {{ $p->nome }}"
                      placeholder="Escreva a resposta…" x-on:keydown.enter="if (! $event.shiftKey && window.matchMedia('(min-width: 768px)').matches) { $event.preventDefault(); $wire.enviar() }"></textarea>
            <button type="submit" class="btn bg-emerald-600 text-white hover:bg-emerald-700" wire:loading.attr="disabled" wire:target="enviar">
                <span wire:loading.remove wire:target="enviar">Enviar</span><span wire:loading wire:target="enviar">Enviando…</span>
            </button>
        </form>
        @error('resposta') <p class="field-error px-3 pb-2">{{ $message }}</p> @enderror
        @if ($flashErro) <p class="px-3 pb-2 text-sm text-red-700" role="alert">{{ $flashErro }}</p> @endif
    @endif
</section>
