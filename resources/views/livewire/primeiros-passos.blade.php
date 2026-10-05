<div>
    @if ($mostrar)
        @php($total = count($passos))
        <section class="card p-5 sm:p-6 mb-6" aria-labelledby="primeiros-passos-titulo" x-data="{ aberto: true }">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 id="primeiros-passos-titulo" class="text-base font-semibold text-stone-900">Primeiros passos</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Deixe a clínica pronta para atender. Você fez {{ $feitos }} de {{ $total }}.</p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="aberto = !aberto" class="btn-ghost px-3" :aria-expanded="aberto">
                        <span x-text="aberto ? 'Recolher' : 'Mostrar'"></span>
                    </button>
                    <button type="button" wire:click="dispensar" wire:confirm="Esconder os primeiros passos? Você pode seguir configurando pelo menu."
                            class="btn-ghost px-3 text-stone-500">Dispensar</button>
                </div>
            </div>

            <div class="mt-4 h-2 rounded-full bg-stone-100 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $feitos }}">
                <div class="h-full rounded-full bg-rose-500 transition-all" style="width: {{ round($feitos / max(1, $total) * 100) }}%"></div>
            </div>

            <ol x-show="aberto" x-collapse class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($passos as $passo)
                    <li class="flex items-start gap-3 rounded-xl border p-4 {{ $passo['feito'] ? 'border-stone-100 bg-stone-50' : 'border-stone-200' }}">
                        @if ($passo['feito'])
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0" aria-label="Concluído">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </span>
                        @else
                            <span class="mt-0.5 w-6 h-6 rounded-full border-2 border-stone-300 shrink-0" aria-label="Pendente"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium {{ $passo['feito'] ? 'text-stone-400 line-through' : 'text-stone-900' }}">{{ $passo['titulo'] }}</p>
                            @unless ($passo['feito'])
                                <p class="mt-0.5 text-sm text-stone-500">{{ $passo['texto'] }}</p>
                                <a href="{{ $passo['rota'] }}" class="mt-2 inline-flex min-h-[44px] items-center text-sm font-semibold text-rose-600 hover:text-rose-700">
                                    {{ $passo['acao'] }} →
                                </a>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</div>
