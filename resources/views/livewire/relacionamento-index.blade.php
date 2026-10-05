@php
    $abas = ['retornos' => 'Retornos', 'aniversarios' => 'Aniversários', 'sumidos' => 'Sumidos', 'pesquisas' => 'Satisfação'];
    $fuso = config('clinica.fuso_horario');
@endphp
<div>
    <x-ui.page-header titulo="Relacionamento" subtitulo="Quem chamar hoje: retornos, aniversários, pacientes que sumiram e pesquisa de satisfação." />

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            {{-- border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    <div class="mb-5 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
        <nav class="flex w-max gap-1 rounded-xl bg-stone-100 p-1" aria-label="Listas de relacionamento">
            @foreach ($abas as $chave => $rotulo)
                <button type="button" wire:click="$set('aba', '{{ $chave }}')"
                        @class([
                            'min-h-11 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors',
                            'bg-surface text-stone-900 shadow-sm' => $aba === $chave,
                            'text-stone-500 hover:text-stone-800' => $aba !== $chave,
                        ])>
                    {{ $rotulo }}
                    @if ($contagens[$chave])
                        <span class="ml-1 rounded-full bg-rose-600 px-1.5 text-xs text-white tabular-nums">{{ $contagens[$chave] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══ Retornos ═══ --}}
    @if ($aba === 'retornos')
        @if ($retornos->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhum retorno para chamar"
                    texto="Defina o retorno sugerido de cada procedimento (ex.: toxina em 120 dias) em Profissionais e horários. Quem estiver no prazo e sem horário marcado aparece aqui."
                    icone="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </div>
        @else
            <div class="card divide-y divide-stone-100">
                @foreach ($retornos as $r)
                    @php $vencido = \Carbon\Carbon::parse($r->retorno_em)->isPast(); @endphp
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="ret-{{ $r->agendamento_id }}">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">{{ $r->nome }}</p>
                            <p class="text-sm text-stone-500">
                                {{ $r->procedimento }} · última vez em {{ \Carbon\Carbon::parse($r->ultima_visita)->format('d/m/Y') }}
                            </p>
                            <p class="text-xs {{ $vencido ? 'text-red-700' : 'text-amber-700' }}">
                                Retorno {{ $vencido ? 'desde' : 'em' }} {{ \Carbon\Carbon::parse($r->retorno_em)->format('d/m/Y') }}
                            </p>
                        </div>
                        @include('livewire.partials.relacionamento-acoes', [
                            'tipo' => 'retorno', 'pacienteId' => $r->paciente_id, 'referencia' => $r->agendamento_id,
                            'aceita' => (bool) $r->aceita_whatsapp_marketing, 'telefone' => $r->telefone, 'procedimento' => $r->procedimento,
                        ])
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ═══ Aniversários ═══ --}}
    @if ($aba === 'aniversarios')
        @if ($aniversarios->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhum aniversariante nos próximos dias"
                    texto="Quem faz aniversário hoje ou nos próximos 7 dias aparece aqui para receber os parabéns."
                    icone="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m15-3.379a48.474 48.474 0 00-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.169c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.625v-5.17c0-1.08.768-2.014 1.837-2.174A47.78 47.78 0 016 13.12M12.265 3.11a.375.375 0 11-.53 0L12 2.845l.265.265zm-3 0a.375.375 0 11-.53 0L9 2.845l.265.265zm6 0a.375.375 0 11-.53 0L15 2.845l.265.265z" />
            </div>
        @else
            <div class="card divide-y divide-stone-100">
                @foreach ($aniversarios as $p)
                    @php $hoje = $p->data_nascimento->format('m-d') === now()->format('m-d'); @endphp
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="niver-{{ $p->id }}">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">
                                {{ $p->nome }}
                                @if ($hoje) <span class="badge ml-1 bg-rose-50 text-rose-700">Hoje 🎉</span> @endif
                            </p>
                            <p class="text-sm text-stone-500">{{ $p->data_nascimento->format('d/m') }} · faz {{ $p->data_nascimento->age + ($hoje ? 0 : 1) }} anos</p>
                        </div>
                        @include('livewire.partials.relacionamento-acoes', [
                            'tipo' => 'aniversario', 'pacienteId' => $p->id, 'referencia' => (string) now()->year,
                            'aceita' => (bool) $p->aceita_whatsapp_marketing, 'telefone' => $p->telefone,
                        ])
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ═══ Sumidos ═══ --}}
    @if ($aba === 'sumidos')
        <div class="mb-4 flex items-center gap-3">
            <label for="meses" class="text-sm text-stone-600">Sem vir há mais de</label>
            <select id="meses" wire:model.live="mesesSumido" class="input w-auto">
                <option value="3">3 meses</option>
                <option value="6">6 meses</option>
                <option value="12">1 ano</option>
            </select>
        </div>
        @if ($sumidos->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Ninguém sumido por aqui"
                    texto="Pacientes ativos que não voltam há algum tempo, sem horário marcado, aparecem aqui para um convite." />
            </div>
        @else
            <div class="card divide-y divide-stone-100">
                @foreach ($sumidos as $s)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="sum-{{ $s->paciente_id }}">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">{{ $s->nome }}</p>
                            <p class="text-sm text-stone-500">
                                Última visita em {{ \Carbon\Carbon::parse($s->ultima_visita)->format('d/m/Y') }}
                                · {{ $s->visitas }} {{ $s->visitas == 1 ? 'atendimento' : 'atendimentos' }}
                            </p>
                        </div>
                        @include('livewire.partials.relacionamento-acoes', [
                            'tipo' => 'sumido', 'pacienteId' => $s->paciente_id, 'referencia' => now()->format('Y-m'),
                            'aceita' => (bool) $s->aceita_whatsapp_marketing, 'telefone' => $s->telefone,
                        ])
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ═══ Satisfação ═══ --}}
    @if ($aba === 'pesquisas')
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-5">
                <p class="text-sm text-stone-500">NPS (90 dias)</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ ($resultado['nps'] ?? 0) >= 50 ? 'text-emerald-700' : 'text-stone-900' }}">
                    {{ $resultado['nps'] ?? '—' }}
                </p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-stone-500">Nota média</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-stone-900">{{ $resultado['media'] !== null ? number_format($resultado['media'], 1, ',', '') : '—' }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-stone-500">Respostas</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-stone-900">{{ $resultado['respostas'] }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-stone-500">Taxa de resposta</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-stone-900">
                    {{ $resultado['enviadas'] ? round($resultado['respostas'] / $resultado['enviadas'] * 100) . '%' : '—' }}
                </p>
            </div>
        </div>

        <h2 class="mb-2 text-sm font-semibold text-stone-700">Atendimentos para enviar a pesquisa</h2>
        @if ($semPesquisa->isEmpty())
            <div class="card mb-6">
                <x-ui.empty-state titulo="Tudo enviado"
                    texto="Os atendimentos realizados nos últimos 7 dias aparecem aqui para receber a pesquisa (nota de 0 a 10)." />
            </div>
        @else
            <div class="card mb-6 divide-y divide-stone-100">
                @foreach ($semPesquisa as $a)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="pq-{{ $a->id }}">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">{{ $a->paciente?->nome }}</p>
                            <p class="text-sm text-stone-500">{{ $a->procedimento?->nome ?? 'Atendimento' }} · {{ $a->inicio_em->format('d/m H:i') }} · {{ $a->profissional?->nome }}</p>
                        </div>
                        @if ($a->paciente?->telefone)
                            <button type="button" class="btn-secondary min-h-10 px-3 text-sm" wire:click="enviarPesquisa('{{ $a->id }}')" wire:loading.attr="disabled" wire:target="enviarPesquisa">
                                Enviar pesquisa
                            </button>
                        @else
                            <span class="badge bg-stone-100 text-stone-600">Sem telefone</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <h2 class="mb-2 text-sm font-semibold text-stone-700">Últimas respostas</h2>
        @if ($resultado['comentarios']->isEmpty())
            <p class="text-sm text-stone-500">Nenhuma resposta ainda.</p>
        @else
            <div class="card divide-y divide-stone-100">
                @foreach ($resultado['comentarios'] as $c)
                    @php $cor = $c->nota >= 9 ? 'bg-emerald-50 text-emerald-700' : ($c->nota >= 7 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'); @endphp
                    <div class="flex gap-3 p-4" wire:key="resp-{{ $c->id }}">
                        <span class="badge h-8 w-8 shrink-0 justify-center text-sm font-semibold tabular-nums {{ $cor }}">{{ $c->nota }}</span>
                        <div class="min-w-0">
                            <p class="text-sm text-stone-800">{{ $c->comentario ?: 'Sem comentário.' }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $c->paciente?->nome }} · {{ $c->profissional?->nome }} · {{ $c->respondida_em->timezone($fuso)->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
