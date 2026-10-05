@php
    $campo  = 'w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100';
    $rotulo = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500';
    $botao  = 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition-colors disabled:opacity-60';
    $botaoSec = 'inline-flex min-h-[44px] items-center justify-center rounded-xl border border-stone-200 bg-surface px-4 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors';
@endphp

<div class="mx-auto max-w-3xl space-y-6">

    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $flashSucesso }}</div>
    @endif
    @if ($flashErro)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $flashErro }}</div>
    @endif

    <div>
        <h1 class="text-xl font-bold text-stone-900">Configurações da clínica</h1>
        <p class="mt-0.5 text-sm text-stone-500">Identidade, integrações e regras financeiras de {{ $clinica->nome }}</p>
    </div>

    {{-- Abas --}}
    <div class="grid grid-cols-3 rounded-xl bg-stone-100 p-1">
        @foreach (['dados' => 'Dados', 'integracoes' => 'Integrações', 'financeiro' => 'Financeiro'] as $chave => $titulo)
            <button type="button" wire:click="$set('aba', '{{ $chave }}')"
                    class="min-h-[40px] rounded-lg text-sm font-semibold transition-colors {{ $aba === $chave ? 'bg-surface text-rose-700 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                {{ $titulo }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════════ Dados ═══════════════ --}}
    @if ($aba === 'dados')
        <form wire:submit="salvarDados" class="space-y-5 rounded-2xl border border-stone-100 bg-surface p-5 shadow-sm sm:p-6">
            <p class="text-sm text-stone-500">Aparecem no menu, nos e-mails e nas mensagens enviadas aos pacientes.</p>

            <div class="flex items-center gap-4">
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="Novo logo" class="h-16 w-16 rounded-xl object-contain ring-1 ring-stone-100">
                @elseif ($clinica->logoUrl())
                    <img src="{{ $clinica->logoUrl() }}" alt="Logo atual" class="h-16 w-16 rounded-xl object-contain ring-1 ring-stone-100">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-rose-600 text-2xl font-bold text-white">{{ mb_strtoupper(mb_substr($clinica->nome, 0, 1)) }}</div>
                @endif
                <div class="min-w-0">
                    <label class="{{ $botaoSec }} cursor-pointer">
                        {{ $clinica->logo_path ? 'Trocar logo' : 'Enviar logo' }}
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="hidden">
                    </label>
                    <p class="mt-1 text-xs text-stone-400">PNG, JPG ou WEBP, até 2 MB.</p>
                    @error('logo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $rotulo }}">Nome da clínica <span class="text-red-400">*</span></label>
                    <input type="text" wire:model="nome" class="{{ $campo }} @error('nome') border-red-300 @enderror">
                    @error('nome') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $rotulo }}">Razão social</label>
                    <input type="text" wire:model="razaoSocial" class="{{ $campo }}">
                </div>
                <div>
                    <label class="{{ $rotulo }}">CNPJ</label>
                    <input type="text" inputmode="numeric" wire:model="cnpj" placeholder="00.000.000/0000-00" class="{{ $campo }} @error('cnpj') border-red-300 @enderror">
                    @error('cnpj') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $rotulo }}">Telefone de contato</label>
                    <input type="tel" wire:model="telefone" placeholder="(61) 99999-0000" class="{{ $campo }}">
                </div>
                <div>
                    <label class="{{ $rotulo }}">E-mail de contato</label>
                    <input type="email" wire:model="emailContato" class="{{ $campo }} @error('emailContato') border-red-300 @enderror">
                    @error('emailContato') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $rotulo }}">Endereço</label>
                    <input type="text" wire:model="endereco" class="{{ $campo }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $rotulo }}">Assinatura / slogan</label>
                    <input type="text" wire:model="slogan" placeholder="Ex.: sua beleza em boas mãos" class="{{ $campo }}">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="{{ $botao }}">Salvar dados</button>
            </div>
        </form>
    @endif

    {{-- ═══════════════ Integrações ═══════════════ --}}
    @if ($aba === 'integracoes')
        <form wire:submit="salvarIntegracoes" class="space-y-6">
            {{-- WhatsApp --}}
            <section class="space-y-4 rounded-2xl border border-stone-100 bg-surface p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold text-stone-800">WhatsApp</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $clinica->whatsappConfigurado() ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">
                        {{ $clinica->whatsappConfigurado() ? 'Conectado' : 'Não configurado' }}
                    </span>
                </div>
                <p class="text-sm text-stone-500">Envia confirmações, lembretes e cobranças aos pacientes pela instância da clínica.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $rotulo }}">Instância</label>
                        <input type="text" wire:model="evolutionInstance" autocomplete="off" class="{{ $campo }} @error('evolutionInstance') border-red-300 @enderror">
                        @error('evolutionInstance') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $rotulo }}">Chave da instância</label>
                        <input type="password" wire:model="evolutionApiKey" autocomplete="new-password"
                               placeholder="{{ $clinica->evolution_api_key ? '•••••••• (configurada — deixe em branco para manter)' : 'Cole a chave' }}" class="{{ $campo }}">
                        @if ($clinica->evolution_api_key)
                            <button type="button" wire:click="removerSegredo('evolution_api_key')" wire:confirm="Remover a chave do WhatsApp? As mensagens deixarão de ser enviadas."
                                    class="mt-1 text-xs font-medium text-red-600 hover:text-red-700">Remover chave</button>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $rotulo }}">Número da gestão</label>
                        <input type="tel" wire:model="whatsappNumero" placeholder="+55 (61) 99999-0000" class="{{ $campo }} @error('whatsappNumero') border-red-300 @enderror">
                        <p class="mt-1 text-xs text-stone-400">Recebe o resumo diário de vencimentos e é o único autorizado a lançar transações por áudio.</p>
                        @error('whatsappNumero') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="button" wire:click="testarWhatsApp" wire:loading.attr="disabled" class="{{ $botaoSec }}">Enviar mensagem de teste</button>
            </section>

            {{-- Asaas --}}
            <section class="space-y-4 rounded-2xl border border-stone-100 bg-surface p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold text-stone-800">Asaas (cobranças)</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $clinica->asaasConfigurado() ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">
                        {{ $clinica->asaasConfigurado() ? 'Conectado' : 'Não configurado' }}
                    </span>
                </div>
                <p class="text-sm text-stone-500">As cobranças Pix dos pacientes são geradas na conta Asaas da própria clínica.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $rotulo }}">Chave da API</label>
                        <input type="password" wire:model="asaasApiKey" autocomplete="new-password"
                               placeholder="{{ $clinica->asaas_api_key ? '•••••••• (configurada — deixe em branco para manter)' : 'Cole a chave ($aact_...)' }}" class="{{ $campo }}">
                        @if ($clinica->asaas_api_key)
                            <button type="button" wire:click="removerSegredo('asaas_api_key')" wire:confirm="Remover a chave do Asaas? Novas cobranças não poderão ser geradas."
                                    class="mt-1 text-xs font-medium text-red-600 hover:text-red-700">Remover chave</button>
                        @endif
                    </div>
                    <label class="flex min-h-[44px] items-center gap-3 sm:col-span-2">
                        <input type="checkbox" wire:model="asaasSandbox" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                        <span class="text-sm text-stone-700">Ambiente de testes (sandbox)</span>
                    </label>
                    <div class="sm:col-span-2">
                        <label class="{{ $rotulo }}">URL do webhook (configure no Asaas)</label>
                        <div class="flex gap-2" x-data="{ copiado: false }">
                            <input type="text" readonly value="{{ $webhookUrl }}" class="{{ $campo }} bg-stone-50 text-stone-600">
                            <button type="button" class="{{ $botaoSec }} shrink-0"
                                    @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copiado = true; setTimeout(() => copiado = false, 2000)"
                                    x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</button>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $rotulo }}">Token do webhook</label>
                        <input type="password" wire:model="asaasWebhookToken" autocomplete="new-password"
                               placeholder="{{ $clinica->asaas_webhook_token ? '•••••••• (configurado — deixe em branco para manter)' : 'O mesmo token informado no Asaas' }}" class="{{ $campo }}">
                    </div>
                </div>
                <button type="button" wire:click="testarAsaas" wire:loading.attr="disabled" class="{{ $botaoSec }}">Testar conexão</button>
            </section>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="{{ $botao }}">Salvar integrações</button>
            </div>
        </form>
    @endif

    {{-- ═══════════════ Financeiro ═══════════════ --}}
    @if ($aba === 'financeiro')
        <form wire:submit="salvarFinanceiro" class="space-y-5 rounded-2xl border border-stone-100 bg-surface p-5 shadow-sm sm:p-6">
            <div class="max-w-xs">
                <label class="{{ $rotulo }}">Alíquota de imposto (%)</label>
                <input type="text" inputmode="decimal" wire:model="aliquotaImposto" class="{{ $campo }} tabular-nums @error('aliquotaImposto') border-red-300 @enderror">
                <p class="mt-1 text-xs text-stone-400">Aplicada às receitas novas (ex.: Simples Nacional). Não altera lançamentos antigos.</p>
                @error('aliquotaImposto') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="rounded-xl bg-stone-50 px-4 py-3 text-sm text-stone-600">
                As taxas de cartão (débito e crédito 1x a 12x) ficam em
                <a href="{{ route('taxas-cartao.index') }}" class="font-semibold text-rose-600 underline">Taxas de cartão</a>.
            </div>
            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="{{ $botao }}">Salvar</button>
            </div>
        </form>
    @endif
</div>
