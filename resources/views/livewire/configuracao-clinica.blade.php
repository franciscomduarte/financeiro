<div>

    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $flashSucesso }}</div>
    @endif
    @if ($flashErro)
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $flashErro }}</div>
    @endif

    <x-ui.page-header titulo="Dados da clínica" subtitulo="Identidade, integrações e regras financeiras de {{ $clinica->nome }}." />

    <div class="max-w-3xl space-y-6">

    {{-- Abas --}}
    <div class="grid grid-cols-3 rounded-xl bg-stone-100 p-1" role="tablist">
        @foreach (['dados' => 'Dados', 'integracoes' => 'Integrações', 'financeiro' => 'Financeiro'] as $chave => $titulo)
            <button type="button" wire:click="$set('aba', '{{ $chave }}')"
                    role="tab" aria-selected="{{ $aba === $chave ? 'true' : 'false' }}"
                    class="min-h-[44px] rounded-lg text-sm font-medium transition-colors {{ $aba === $chave ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                {{ $titulo }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════════ Dados ═══════════════ --}}
    @if ($aba === 'dados')
        <form wire:submit="salvarDados" class="card space-y-5 p-5 sm:p-6">
            <p class="text-sm text-stone-500">Esses dados aparecem no menu, nos e-mails e nas mensagens que seus pacientes recebem.</p>

            <div class="flex items-center gap-4">
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="Novo logo" class="h-16 w-16 rounded-xl object-contain ring-1 ring-stone-200">
                @elseif ($clinica->logoUrl())
                    <img src="{{ $clinica->logoUrl() }}" alt="Logo atual" class="h-16 w-16 rounded-xl object-contain ring-1 ring-stone-200">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-rose-600 text-2xl font-bold text-white">{{ mb_strtoupper(mb_substr($clinica->nome, 0, 1)) }}</div>
                @endif
                <div class="min-w-0">
                    <label class="btn-secondary cursor-pointer">
                        {{ $clinica->logo_path ? 'Trocar logo' : 'Enviar logo' }}
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="hidden">
                    </label>
                    <p class="hint">PNG, JPG ou WEBP, até 2 MB.</p>
                    @error('logo') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Nome da clínica <span class="text-rose-600">*</span></label>
                    <input type="text" wire:model="nome" class="input @error('nome') border-red-300 @enderror">
                    @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Razão social</label>
                    <input type="text" wire:model="razaoSocial" class="input">
                </div>
                <div>
                    <label class="label">CNPJ</label>
                    <input type="text" inputmode="numeric" wire:model="cnpj" placeholder="Ex.: 12.345.678/0001-90" class="input @error('cnpj') border-red-300 @enderror">
                    @error('cnpj') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Telefone de contato</label>
                    <input type="tel" wire:model="telefone" placeholder="Ex.: (61) 99999-0000" class="input">
                </div>
                <div>
                    <label class="label">E-mail de contato</label>
                    <input type="email" wire:model="emailContato" class="input @error('emailContato') border-red-300 @enderror">
                    @error('emailContato') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Endereço</label>
                    <input type="text" wire:model="endereco" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Slogan</label>
                    <input type="text" wire:model="slogan" placeholder="Ex.: Sua beleza em boas mãos" class="input">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar alterações</button>
            </div>
        </form>
    @endif

    {{-- ═══════════════ Integrações ═══════════════ --}}
    @if ($aba === 'integracoes')
        <form wire:submit="salvarIntegracoes" class="space-y-6">
            {{-- WhatsApp --}}
            <section class="card space-y-4 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-stone-900">WhatsApp</h2>
                    <span class="badge {{ $clinica->whatsappConfigurado() ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                        {{ $clinica->whatsappConfigurado() ? 'Conectado' : 'Não configurado' }}
                    </span>
                </div>
                <p class="text-sm text-stone-500">Envie confirmações, lembretes e cobranças aos pacientes pelo WhatsApp da clínica.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Instância</label>
                        <input type="text" wire:model="evolutionInstance" autocomplete="off" class="input @error('evolutionInstance') border-red-300 @enderror">
                        @error('evolutionInstance') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Chave da instância</label>
                        <input type="password" wire:model="evolutionApiKey" autocomplete="new-password"
                               placeholder="{{ $clinica->evolution_api_key ? '•••••••• (já configurada; deixe em branco para manter)' : 'Cole aqui a chave da instância' }}" class="input">
                        @if ($clinica->evolution_api_key)
                            <button type="button" wire:click="removerSegredo('evolution_api_key')" wire:confirm="Remover a chave do WhatsApp? As mensagens aos pacientes deixam de ser enviadas."
                                    class="mt-1 inline-flex min-h-[44px] items-center text-sm font-medium text-red-600 hover:text-red-700">Remover chave</button>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">WhatsApp da gestão</label>
                        <input type="tel" wire:model="whatsappNumero" placeholder="Ex.: +55 (61) 99999-0000" class="input @error('whatsappNumero') border-red-300 @enderror">
                        <p class="hint">Recebe o resumo diário de vencimentos. Só este número pode registrar lançamentos por áudio.</p>
                        @error('whatsappNumero') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="button" wire:click="testarWhatsApp" wire:loading.attr="disabled" class="btn-secondary">Enviar mensagem de teste</button>
            </section>

            {{-- Asaas --}}
            <section class="card space-y-4 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-stone-900">Asaas (cobranças)</h2>
                    <span class="badge {{ $clinica->asaasConfigurado() ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                        {{ $clinica->asaasConfigurado() ? 'Conectado' : 'Não configurado' }}
                    </span>
                </div>
                <p class="text-sm text-stone-500">As cobranças Pix dos pacientes saem da conta Asaas da própria clínica.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Chave da API</label>
                        <input type="password" wire:model="asaasApiKey" autocomplete="new-password"
                               placeholder="{{ $clinica->asaas_api_key ? '•••••••• (já configurada; deixe em branco para manter)' : 'Ex.: $aact_...' }}" class="input">
                        @if ($clinica->asaas_api_key)
                            <button type="button" wire:click="removerSegredo('asaas_api_key')" wire:confirm="Remover a chave do Asaas? Você não vai conseguir gerar novas cobranças."
                                    class="mt-1 inline-flex min-h-[44px] items-center text-sm font-medium text-red-600 hover:text-red-700">Remover chave</button>
                        @endif
                    </div>
                    <label class="flex min-h-[44px] items-center gap-3 sm:col-span-2">
                        <input type="checkbox" wire:model="asaasSandbox" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                        <span class="text-sm text-stone-700">Ambiente de testes (sandbox)</span>
                    </label>
                    <div class="sm:col-span-2">
                        <label class="label">Endereço do webhook</label>
                        <div class="flex gap-2" x-data="{ copiado: false }">
                            <input type="text" readonly value="{{ $webhookUrl }}" class="input min-w-0 bg-stone-50 text-stone-600">
                            <button type="button" class="btn-secondary shrink-0"
                                    @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copiado = true; setTimeout(() => copiado = false, 2000)"
                                    x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</button>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Token do webhook</label>
                        <input type="password" wire:model="asaasWebhookToken" autocomplete="new-password"
                               placeholder="{{ $clinica->asaas_webhook_token ? '•••••••• (já configurado; deixe em branco para manter)' : 'O mesmo token informado no Asaas' }}" class="input">
                    </div>
                </div>
                <button type="button" wire:click="testarAsaas" wire:loading.attr="disabled" class="btn-secondary">Testar conexão</button>
            </section>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar integrações</button>
            </div>
        </form>
    @endif

    {{-- ═══════════════ Financeiro ═══════════════ --}}
    @if ($aba === 'financeiro')
        <form wire:submit="salvarFinanceiro" class="card space-y-5 p-5 sm:p-6">
            <div class="max-w-xs">
                <label class="label">Alíquota de imposto (%)</label>
                <input type="text" inputmode="decimal" wire:model="aliquotaImposto" class="input tabular-nums @error('aliquotaImposto') border-red-300 @enderror">
                <p class="hint">Vale para as receitas novas (ex.: Simples Nacional). Lançamentos antigos não mudam.</p>
                @error('aliquotaImposto') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="rounded-xl bg-stone-50 px-4 py-3 text-sm text-stone-600">
                As taxas de cartão (débito e crédito de 1x a 12x) ficam em
                <a href="{{ route('taxas-cartao.index') }}" class="font-medium text-rose-600 underline underline-offset-2 hover:text-rose-700">Taxas de cartão</a>.
            </div>
            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar alterações</button>
            </div>
        </form>
    @endif
    </div>
</div>
