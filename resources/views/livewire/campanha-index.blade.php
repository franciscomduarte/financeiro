<div>
    <x-ui.page-header titulo="Campanhas" subtitulo="Mensagens de WhatsApp para os pacientes que você escolher, enviadas aos poucos para o número não ser bloqueado.">
        <x-slot:acoes>
            @podeEditar
                <button type="button" wire:click="nova" wire:loading.attr="disabled" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nova campanha
                </button>
            @endpodeEditar
        </x-slot:acoes>
    </x-ui.page-header>

    @if ($flashErro)
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $flashErro }}</div>
    @endif

    <div class="mb-5 flex flex-col gap-1 rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900 sm:flex-row sm:items-center sm:justify-between">
        <p>🛡️ {{ $regras }}</p>
        <p class="shrink-0 font-medium tabular-nums">Hoje: {{ $enviadasHoje }} de {{ $limite }}</p>
    </div>

    @if ($campanhas->isEmpty())
        <x-ui.empty-state titulo="Nenhuma campanha ainda"
            texto="Crie uma campanha, escreva a mensagem e escolha os pacientes. O sistema envia aos poucos, em horário comercial." />
    @else
        <ul class="card divide-y divide-stone-100">
            @foreach ($campanhas as $c)
                @php $total = $c->total ?: $c->destinatarios_count; $feitos = $c->enviados + $c->falhas; @endphp
                <li wire:key="camp-{{ $c->id }}">
                    <a href="{{ route('campanhas.editar', $c->id) }}" wire:navigate class="flex min-h-11 items-center gap-4 px-4 py-3 hover:bg-stone-50 sm:px-5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-stone-900">{{ $c->nome }}</p>
                            <p class="text-xs text-stone-500 tabular-nums">
                                {{ $total }} {{ $total === 1 ? 'paciente' : 'pacientes' }}
                                @if (! $c->status->editavel()) · {{ $c->enviados }} enviadas @if ($c->falhas) · {{ $c->falhas }} com falha @endif @endif
                                · criada em {{ $c->created_at->format('d/m/Y') }}
                            </p>
                            @if (in_array($c->status, [\App\Enums\StatusCampanha::Enviando, \App\Enums\StatusCampanha::Pausada], true) && $total > 0)
                                <div class="mt-2 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-stone-100">
                                    <div class="h-full rounded-full bg-sky-500" style="width: {{ min(100, (int) round($feitos / $total * 100)) }}%"></div>
                                </div>
                            @endif
                        </div>
                        <span class="badge shrink-0 {{ $c->status->badge() }}">{{ $c->status->label() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $campanhas->links() }}</div>
    @endif
</div>
