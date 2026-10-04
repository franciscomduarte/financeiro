{{-- Legenda do calendário: cor por profissional (toque para filtrar) e cor por status --}}
@use('App\Enums\StatusAgendamento')
@use('App\Services\AgendaCalendarioService')

<div class="flex flex-col gap-3 rounded-2xl border border-stone-100 bg-white px-4 py-3 shadow-sm md:flex-row md:items-start md:gap-6">
    <div class="min-w-0">
        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-stone-400">Profissionais</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($this->profissionais as $p)
                @php $ativo = $filtroProfissionalId === $p->id; @endphp
                <button type="button" wire:key="leg-prof-{{ $p->id }}"
                        wire:click="$set('filtroProfissionalId', '{{ $ativo ? '' : $p->id }}')"
                        title="{{ $ativo ? 'Mostrar todos' : 'Mostrar só ' . $p->nome }}"
                        class="flex min-h-[32px] items-center gap-1.5 rounded-full border px-2.5 text-xs font-medium transition-colors
                            {{ $ativo ? 'border-violet-300 bg-violet-50 text-violet-700' : 'border-stone-200 text-stone-600 hover:bg-stone-50' }}">
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ AgendaCalendarioService::corSegura($p->cor_agenda) }}"></span>
                    {{ $p->nome }}
                </button>
            @endforeach
        </div>
    </div>
    @if ($visaoAtual !== \App\Enums\VisaoAgenda::Mes)
        <div class="min-w-0">
            <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-stone-400">Status (bolinha)</p>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-stone-600">
                @foreach (StatusAgendamento::cases() as $status)
                    <span class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full {{ $status->corPonto() }}"></span>{{ $status->label() }}
                    </span>
                @endforeach
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-4 rounded-sm border border-stone-300"
                          style="background-image: repeating-linear-gradient(135deg, rgb(214 211 209 / 0.7) 0 3px, rgb(245 245 244) 3px 6px);"></span>Bloqueado
                </span>
            </div>
        </div>
    @endif
</div>
