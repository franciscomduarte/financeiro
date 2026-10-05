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
    <div class="grid grid-cols-2 gap-1 rounded-xl bg-stone-100 p-1 sm:grid-cols-4" role="tablist">
        @foreach (['dados' => 'Dados', 'integracoes' => 'Integrações', 'financeiro' => 'Financeiro', 'nota_fiscal' => 'Nota fiscal'] as $chave => $titulo)
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

    {{-- ═══════════════ Nota fiscal ═══════════════ --}}
    @if ($aba === 'nota_fiscal')
        @php $pendencias = $clinica->pendenciasNfse(); @endphp
        <form wire:submit="salvarNotaFiscal" class="card space-y-5 p-5 sm:p-6">
            <div>
                <h2 class="text-base font-semibold text-stone-900">NFS-e pela Focus NFe</h2>
                <p class="mt-1 text-sm text-stone-500">
                    Emita a nota de serviço direto dos lançamentos. Crie a conta em focusnfe.com.br, cadastre a empresa com o certificado digital A1
                    e cole aqui o token. Os dados fiscais (item da lista, código de tributação e alíquota) estão no seu cadastro na prefeitura ou com o contador.
                </p>
            </div>

            @if ($pendencias)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Falta para emitir: {{ implode(', ', $pendencias) }}.</div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Tudo pronto para emitir {{ $clinica->nfse_homologacao ? 'em homologação (notas de teste, sem valor fiscal)' : 'notas de verdade (produção)' }}.
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nfse-token" class="label">Token da Focus NFe</label>
                    <div class="flex gap-2">
                        <input id="nfse-token" type="password" wire:model="nfseToken" autocomplete="off" class="input"
                               placeholder="{{ $clinica->nfse_token ? '•••••••• (salvo; preencha só para trocar)' : 'Cole o token da empresa na Focus NFe' }}">
                        @if ($clinica->nfse_token)
                            <button type="button" wire:click="removerSegredo('nfse_token')" wire:confirm="Remover o token da Focus NFe? A emissão de notas para." class="btn-ghost shrink-0 text-red-600">Remover</button>
                        @endif
                    </div>
                </div>
                <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700 sm:col-span-2">
                    <input type="checkbox" wire:model="nfseHomologacao" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                    Ambiente de homologação (testes, sem valor fiscal). Desmarque só quando a Focus liberar a produção.
                </label>
                <div class="sm:col-span-2">
                    <label for="nfse-padrao" class="label">Padrão da nota</label>
                    <select id="nfse-padrao" wire:model.live="nfsePadrao" class="input">
                        @foreach (\App\Enums\PadraoNfse::cases() as $p)
                            <option value="{{ $p->value }}">{{ $p->label() }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Veja no painel da Focus NFe, no cadastro da empresa, se o seu município emite pela NFS-e Nacional. Na dúvida, pergunte ao contador.</p>
                    @error('nfsePadrao') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nfse-im" class="label">Inscrição municipal{{ $nfsePadrao === 'nacional' ? ' (opcional)' : '' }}</label>
                    <input id="nfse-im" type="text" wire:model="inscricaoMunicipal" maxlength="30" class="input" placeholder="Ex.: 0812345600172">
                    @error('inscricaoMunicipal') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nfse-municipio" class="label">Código IBGE do município</label>
                    <input id="nfse-municipio" type="text" inputmode="numeric" wire:model="codigoMunicipio" maxlength="7" class="input tabular-nums" placeholder="Ex.: 5300108 (Brasília)">
                    @error('codigoMunicipio') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @if ($nfsePadrao === 'nacional')
                <div>
                    <label for="nfse-ctn" class="label">Código de tributação nacional</label>
                    <input id="nfse-ctn" type="text" inputmode="numeric" wire:model="nfseCodigoNacional" maxlength="6" class="input tabular-nums" placeholder="Ex.: 060201">
                    <p class="hint">6 números, conforme a tabela da NFS-e Nacional.</p>
                    @error('nfseCodigoNacional') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @endif
                <div>
                    <label for="nfse-item" class="label">Item da lista de serviço (LC 116){{ $nfsePadrao === 'nacional' ? ' (opcional)' : '' }}</label>
                    <input id="nfse-item" type="text" wire:model="nfseItemListaServico" maxlength="10" class="input" placeholder="Ex.: 06.02 (estética)">
                </div>
                <div>
                    <label for="nfse-codigo" class="label">Código de tributação do município (opcional)</label>
                    <input id="nfse-codigo" type="text" wire:model="nfseCodigoTributario" maxlength="30" class="input" placeholder="Conforme a prefeitura">
                </div>
                <div>
                    <label for="nfse-aliquota" class="label">Alíquota do ISS (%)</label>
                    <input id="nfse-aliquota" type="text" inputmode="decimal" wire:model="nfseAliquotaIss" class="input tabular-nums" placeholder="Ex.: 2">
                    @error('nfseAliquotaIss') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <label class="flex min-h-11 items-center gap-3 self-end text-sm text-stone-700">
                    <input type="checkbox" wire:model="nfseOptanteSimples" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                    Empresa optante pelo Simples Nacional
                </label>
                <div class="sm:col-span-2">
                    <label for="nfse-discriminacao" class="label">Texto padrão da nota (opcional)</label>
                    <textarea id="nfse-discriminacao" wire:model="nfseDiscriminacao" rows="2" maxlength="1000" class="input"
                              placeholder="Ex.: Serviços de estética prestados pela clínica."></textarea>
                    <p class="hint">Vai no início da descrição da nota; a descrição do lançamento é acrescentada depois.</p>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar alterações</button>
            </div>
        </form>
    @endif
    </div>
</div>
