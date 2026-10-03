{{-- Visões Dia e Semana: grade de horários com eventos posicionados por horário --}}
@use('App\Enums\StatusAgendamento')
@use('App\Services\AgendaCalendarioService')
@php
    $slotPx   = 48; // altura de um slot de 30 min (touch target ≥ 44px)
    $minSlot  = AgendaCalendarioService::MINUTOS_POR_SLOT;
    $pxPorMin = $slotPx / $minSlot;
    [$faixaIni, $faixaFim] = $calFaixa;
    $alturaPx = ($faixaFim - $faixaIni) * $pxPorMin;
    $agora    = now();
    $minAgora = $agora->hour * 60 + $agora->minute;
    $nomesDia = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
    $colunas  = 'grid-template-columns: 3.5rem repeat(' . count($calDias) . ', minmax(0, 1fr));';
    $corStatus = fn (StatusAgendamento $s) => match ($s) {
        StatusAgendamento::Agendado   => 'bg-violet-500',
        StatusAgendamento::Confirmado => 'bg-emerald-500',
        StatusAgendamento::Realizado  => 'bg-sky-500',
        StatusAgendamento::Cancelado  => 'bg-red-500',
        StatusAgendamento::Reagendado => 'bg-amber-500',
        StatusAgendamento::Falta      => 'bg-stone-400',
    };
@endphp

<div class="rounded-2xl border border-stone-100 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <div class="{{ count($calDias) > 1 ? 'min-w-[720px]' : '' }}">

            {{-- Cabeçalho dos dias --}}
            <div class="grid border-b border-stone-100 bg-stone-50" style="{{ $colunas }}">
                <div></div>
                @foreach ($calDias as $dia)
                    <button type="button" wire:click="irParaDia('{{ $dia->toDateString() }}')"
                            class="flex min-h-[44px] flex-col items-center justify-center py-2 border-l border-stone-100 hover:bg-violet-50 transition-colors">
                        <span class="text-[11px] font-semibold uppercase tracking-wide {{ $dia->isToday() ? 'text-violet-600' : 'text-stone-400' }}">{{ $nomesDia[$dia->dayOfWeekIso] }}</span>
                        <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold tabular-nums {{ $dia->isToday() ? 'bg-violet-600 text-white' : 'text-stone-800' }}">{{ $dia->day }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Corpo --}}
            <div class="grid" style="{{ $colunas }}">
                {{-- Coluna de horas --}}
                <div class="relative" style="height: {{ $alturaPx }}px">
                    @for ($m = $faixaIni + 60; $m < $faixaFim; $m += 60)
                        <span class="absolute right-2 -translate-y-1/2 text-[11px] tabular-nums text-stone-400"
                              style="top: {{ ($m - $faixaIni) * $pxPorMin }}px">{{ sprintf('%02d:00', intdiv($m, 60)) }}</span>
                    @endfor
                </div>

                @foreach ($calDias as $dia)
                    @php $chave = $dia->toDateString(); @endphp
                    <div class="relative border-l border-stone-100 {{ $dia->isToday() ? 'bg-violet-50/30' : '' }}"
                         style="height: {{ $alturaPx }}px" wire:key="cal-dia-{{ $chave }}">

                        {{-- Slots: clicar num horário livre abre "Novo agendamento" já preenchido --}}
                        @for ($m = $faixaIni; $m < $faixaFim; $m += $minSlot)
                            @php
                                $hora    = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                                $passado = $dia->setTime(intdiv($m, 60), $m % 60)->lt($agora);
                                $linha   = $m % 60 === 0 ? 'border-stone-100' : 'border-dashed border-stone-100/70';
                            @endphp
                            @if ($passado)
                                <div class="border-t {{ $linha }} bg-stone-50/50" style="height: {{ $slotPx }}px"></div>
                            @else
                                <button type="button" wire:click="novoNoHorario('{{ $chave }}', '{{ $hora }}')"
                                        title="Novo agendamento às {{ $hora }}"
                                        class="group block w-full border-t {{ $linha }} text-left hover:bg-violet-50 transition-colors"
                                        style="height: {{ $slotPx }}px">
                                    <span class="hidden pl-2 text-[11px] font-semibold text-violet-500 group-hover:inline">+ {{ $hora }}</span>
                                </button>
                            @endif
                        @endfor

                        {{-- Linha do horário atual --}}
                        @if ($dia->isToday() && $minAgora >= $faixaIni && $minAgora < $faixaFim)
                            <div class="pointer-events-none absolute inset-x-0 z-20 flex items-center"
                                 style="top: {{ ($minAgora - $faixaIni) * $pxPorMin }}px">
                                <span class="-ml-1 h-2 w-2 rounded-full bg-red-500"></span>
                                <span class="h-px flex-1 bg-red-500"></span>
                            </div>
                        @endif

                        {{-- Agendamentos --}}
                        @foreach ($calLayout[$chave] ?? [] as $item)
                            @php
                                $ag      = $item['agendamento'];
                                $iniMin  = $ag->inicio_em->hour * 60 + $ag->inicio_em->minute;
                                $fimMin  = $ag->fim_em->isSameDay($ag->inicio_em) ? $ag->fim_em->hour * 60 + $ag->fim_em->minute : 24 * 60;
                                $altura  = max(24, ($fimMin - $iniMin) * $pxPorMin - 2);
                                $largura = 100 / $item['colunas'];
                                $cor     = AgendaCalendarioService::corSegura($ag->profissional?->cor_agenda);
                                $inativo = ! $ag->status->isPendente() && $ag->status !== StatusAgendamento::Realizado;
                            @endphp
                            <button type="button" wire:key="cal-ag-{{ $ag->id }}"
                                    wire:click="abrirDetalhe('{{ $ag->id }}')"
                                    title="{{ $ag->inicio_em->format('H:i') }} · {{ $ag->paciente?->nome ?? '—' }} · {{ $ag->profissional?->nome ?? '—' }} · {{ $ag->status->label() }}"
                                    class="absolute z-10 flex flex-col justify-start overflow-hidden rounded-lg px-2 py-1 text-left shadow-sm ring-1 ring-black/5 hover:z-30 hover:shadow-md transition-shadow {{ $inativo ? 'opacity-60' : '' }}"
                                    style="top: {{ ($iniMin - $faixaIni) * $pxPorMin + 1 }}px; height: {{ $altura }}px; left: calc({{ $item['coluna'] * $largura }}% + 2px); width: calc({{ $largura }}% - 4px); background-color: color-mix(in srgb, {{ $cor }} 14%, white); border-left: 3px solid {{ $cor }};">
                                <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full {{ $corStatus($ag->status) }}"></span>
                                <p class="truncate pr-2 text-[11px] font-semibold tabular-nums text-stone-600">
                                    {{ $ag->inicio_em->format('H:i') }}<span class="hidden sm:inline">–{{ $ag->fim_em->format('H:i') }}</span>
                                </p>
                                <p class="truncate text-xs font-semibold text-stone-900 {{ $ag->status === StatusAgendamento::Cancelado ? 'line-through' : '' }}">
                                    {{ $ag->paciente?->nome ?? '—' }}
                                </p>
                                @if ($altura >= 60)
                                    <p class="truncate text-[11px] text-stone-500">{{ $ag->procedimento?->nome ?? '' }}</p>
                                @endif
                                @if ($altura >= 84 && ! $filtroProfissionalId)
                                    <p class="truncate text-[11px] text-stone-500">{{ $ag->profissional?->nome ?? '' }}</p>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
