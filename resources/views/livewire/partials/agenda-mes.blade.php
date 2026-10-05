{{-- Visão Mês: grade mensal; tocar num dia abre a visão Dia daquela data --}}
@use('App\Enums\StatusAgendamento')
@use('App\Services\AgendaCalendarioService')
@php
    $maxItens = 3;
@endphp

<div class="card overflow-hidden">
    <div class="grid grid-cols-7 border-b border-stone-100 bg-stone-50">
        @foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $nome)
            <div class="py-2 text-center text-[11px] font-medium uppercase tracking-wide text-stone-500">{{ $nome }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7">
        @foreach ($calDias as $dia)
            @php
                $doDia     = $calPorDia[$dia->toDateString()] ?? collect();
                $foraDoMes = $dia->month !== $calReferencia->month;
            @endphp
            <button type="button" wire:key="cal-mes-{{ $dia->toDateString() }}"
                    wire:click="irParaDia('{{ $dia->toDateString() }}')"
                    class="flex min-h-[64px] flex-col items-stretch justify-start gap-1 border-b border-r border-stone-100 p-1.5 text-left transition-colors hover:bg-rose-50/60 md:min-h-[112px] [&:nth-child(7n)]:border-r-0 {{ $foraDoMes ? 'bg-stone-50/70' : '' }}">
                <span class="flex h-6 w-6 items-center justify-center self-center rounded-full text-xs font-semibold tabular-nums md:self-start
                             {{ $dia->isToday() ? 'bg-rose-600 text-white' : ($foraDoMes ? 'text-stone-400' : 'text-stone-800') }}">
                    {{ $dia->day }}
                </span>

                @foreach ($calBloqueios[$dia->toDateString()] ?? [] as $seg)
                    @php $b = $seg['bloqueio']; @endphp
                    <span class="truncate rounded px-1.5 py-0.5 text-center text-[10px] font-semibold text-stone-600 md:text-left md:text-[11px]"
                          title="Bloqueado · {{ $b['rotulo'] }}{{ $b['motivo'] ? ' · ' . $b['motivo'] : '' }}"
                          style="background-image: repeating-linear-gradient(135deg, color-mix(in srgb, var(--color-stone-300) 60%, transparent) 0 4px, var(--color-stone-100) 4px 8px);">
                        <span class="md:hidden">Bloq.</span>
                        <span class="hidden md:inline">{{ $b['dia_inteiro'] ? '' : sprintf('%02d:%02d ', intdiv($seg['inicio'], 60), $seg['inicio'] % 60) }}Bloqueado{{ $filtroProfissionalId ? '' : ' · ' . $b['rotulo'] }}</span>
                    </span>
                @endforeach

                @if ($doDia->isNotEmpty())
                    {{-- Celular: bolinhas coloridas + total --}}
                    <span class="flex flex-wrap items-center justify-center gap-0.5 md:hidden">
                        @foreach ($doDia->take(4) as $ag)
                            <span class="h-1.5 w-1.5 rounded-full"
                                  style="background-color: {{ AgendaCalendarioService::corSegura($ag->profissional?->cor_agenda) }}"></span>
                        @endforeach
                        @if ($doDia->count() > 4)
                            <span class="text-[10px] font-medium text-stone-500">+{{ $doDia->count() - 4 }}</span>
                        @endif
                    </span>

                    {{-- Desktop: primeiros horários do dia --}}
                    <span class="hidden flex-col gap-0.5 md:flex">
                        @foreach ($doDia->take($maxItens) as $ag)
                            @php $cor = AgendaCalendarioService::corSegura($ag->profissional?->cor_agenda); @endphp
                            <span class="truncate rounded px-1.5 py-0.5 text-[11px] text-stone-700 {{ $ag->status === StatusAgendamento::Cancelado ? 'line-through opacity-60' : '' }}"
                                  style="background-color: color-mix(in srgb, {{ $cor }} 14%, var(--color-surface)); border-left: 2px solid {{ $cor }};">
                                <span class="font-semibold tabular-nums">{{ $ag->inicio_em->format('H:i') }}</span>
                                {{ $ag->paciente?->nome ?? '—' }}
                            </span>
                        @endforeach
                        @if ($doDia->count() > $maxItens)
                            <span class="px-1.5 text-[11px] font-medium text-rose-700">+{{ $doDia->count() - $maxItens }} mais</span>
                        @endif
                    </span>
                @endif
            </button>
        @endforeach
    </div>
</div>
