{{-- Legenda do calendário: cor por profissional (toque para filtrar) e cor por status --}}
@use('App\Enums\StatusAgendamento')
@use('App\Services\AgendaCalendarioService')

<div class="card flex flex-col gap-3 px-4 py-3 md:flex-row md:items-start md:gap-6">
    <div class="min-w-0">
        <p class="mb-1.5 text-xs font-medium text-stone-500">Profissionais</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($this->profissionais as $p)
                @php $ativo = $filtroProfissionalId === $p->id; @endphp
                <button type="button" wire:key="leg-prof-{{ $p->id }}"
                        wire:click="$set('filtroProfissionalId', '{{ $ativo ? '' : $p->id }}')"
                        title="{{ $ativo ? 'Mostrar todos' : 'Mostrar só ' . $p->nome }}"
                        class="flex min-h-[36px] items-center gap-1.5 rounded-full border px-2.5 text-xs font-medium transition-colors
                            {{ $ativo ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-stone-200 text-stone-600 hover:bg-stone-50' }}">
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ AgendaCalendarioService::corSegura($p->cor_agenda) }}"></span>
                    {{ $p->nome }}
                </button>
            @endforeach
        </div>
    </div>
    @if ($visaoAtual !== \App\Enums\VisaoAgenda::Mes)
        <div class="min-w-0">
            <p class="mb-1.5 text-xs font-medium text-stone-500">Status (bolinha no cartão)</p>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-stone-600">
                @foreach (StatusAgendamento::cases() as $status)
                    <span class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full {{ $status->corPonto() }}"></span>{{ $status->label() }}
                    </span>
                @endforeach
                <span class="flex items-center gap-1.5">
                    <span class="h-3 w-4 rounded-sm border border-stone-300"
                          style="background-image: repeating-linear-gradient(135deg, color-mix(in srgb, var(--color-stone-300) 70%, transparent) 0 3px, var(--color-stone-100) 3px 6px);"></span>Bloqueado
                </span>
            </div>
        </div>
    @endif
</div>
