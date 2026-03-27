<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de Gestão LC Estética e Saúde Integrativa">
    <title>LC Estética — Gestão</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-stone-50 font-sans antialiased">

    {{-- Header --}}
    <header class="bg-white border-b border-stone-100 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-rose-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-stone-800 leading-none">LC Estética</p>
                    <p class="text-xs text-stone-400 mt-0.5">Saúde Integrativa</p>
                </div>
            </div>
            <span class="text-xs bg-rose-50 text-rose-600 border border-rose-100 px-2.5 py-1 rounded-full font-medium">
                Sistema interno
            </span>
        </div>
    </header>

    {{-- Main --}}
    <main class="max-w-6xl mx-auto px-6 py-12">

        {{-- Hero --}}
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-bold text-stone-800 mb-3">
                Gestão LC Estética
            </h1>
            <p class="text-stone-500 text-base md:text-lg max-w-xl mx-auto">
                Controle financeiro, contratos, fornecedores e manutenções da clínica em um único lugar.
            </p>
        </div>

        {{-- Módulos --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">

            {{-- Financeiro --}}
            <div class="bg-white rounded-2xl border border-stone-100 p-6 hover:shadow-md transition-shadow">
                <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="font-semibold text-stone-800">Financeiro</h2>
                    <span class="text-xs bg-emerald-50 text-emerald-600 border border-emerald-100 px-2 py-0.5 rounded-full">Fase 1 ✓</span>
                </div>
                <p class="text-sm text-stone-500">Entradas, saídas, implantação vs operação, taxas de cartão e upload de boletos/comprovantes.</p>
            </div>

            {{-- Contratos --}}
            <div class="bg-white rounded-2xl border border-stone-100 p-6 hover:shadow-md transition-shadow opacity-60">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="font-semibold text-stone-800">Contratos e Fornecedores</h2>
                    <span class="text-xs bg-stone-100 text-stone-400 border border-stone-200 px-2 py-0.5 rounded-full">Fase 2</span>
                </div>
                <p class="text-sm text-stone-500">Cadastro de fornecedores, vigência de contratos, reajustes e alertas de vencimento.</p>
            </div>

            {{-- Manutenção --}}
            <div class="bg-white rounded-2xl border border-stone-100 p-6 hover:shadow-md transition-shadow opacity-60">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="font-semibold text-stone-800">Manutenção Preventiva</h2>
                    <span class="text-xs bg-stone-100 text-stone-400 border border-stone-200 px-2 py-0.5 rounded-full">Fase 3</span>
                </div>
                <p class="text-sm text-stone-500">Equipamentos, plano de manutenção, histórico e próximas datas calculadas automaticamente.</p>
            </div>

            {{-- Dashboard --}}
            <div class="bg-white rounded-2xl border border-stone-100 p-6 hover:shadow-md transition-shadow opacity-60">
                <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="font-semibold text-stone-800">Dashboard</h2>
                    <span class="text-xs bg-stone-100 text-stone-400 border border-stone-200 px-2 py-0.5 rounded-full">Fase 3</span>
                </div>
                <p class="text-sm text-stone-500">KPIs operacionais, painel de implantação, projeção de caixa e alertas automáticos.</p>
            </div>

            {{-- Projeção --}}
            <div class="bg-white rounded-2xl border border-stone-100 p-6 hover:shadow-md transition-shadow opacity-60">
                <div class="w-10 h-10 bg-sky-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="font-semibold text-stone-800">Projeção Financeira</h2>
                    <span class="text-xs bg-stone-100 text-stone-400 border border-stone-200 px-2 py-0.5 rounded-full">Fase 3</span>
                </div>
                <p class="text-sm text-stone-500">Fluxo de caixa projetado para 6 meses com base em recorrências e contratos ativos.</p>
            </div>

            {{-- API --}}
            <div class="bg-white rounded-2xl border border-stone-100 p-6 hover:shadow-md transition-shadow">
                <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                    </svg>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="font-semibold text-stone-800">API REST</h2>
                    <span class="text-xs bg-emerald-50 text-emerald-600 border border-emerald-100 px-2 py-0.5 rounded-full">Fase 1 ✓</span>
                </div>
                <p class="text-sm text-stone-500">
                    Endpoints disponíveis em
                    <code class="bg-stone-100 text-stone-700 px-1.5 py-0.5 rounded text-xs">/api/v1/</code>
                    com autenticação via Sanctum.
                </p>
            </div>

        </div>

        {{-- Endpoints ativos --}}
        <div class="bg-white border border-stone-100 rounded-2xl p-6">
            <h3 class="text-sm font-semibold text-stone-700 mb-4">Endpoints ativos — Fase 1</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                @foreach ([
                    ['POST /api/v1/transacoes', 'Criar transação'],
                    ['GET /api/v1/transacoes', 'Listar com filtros'],
                    ['GET /api/v1/transacoes/{id}', 'Detalhe + anexos'],
                    ['PUT /api/v1/transacoes/{id}', 'Atualizar transação'],
                    ['DELETE /api/v1/transacoes/{id}', 'Cancelar transação'],
                    ['POST /api/v1/transacoes/{id}/anexos', 'Upload boleto/comprovante'],
                    ['GET /api/v1/transacoes/{id}/anexos', 'Listar anexos'],
                    ['GET /api/v1/anexos/{id}/download', 'Download seguro'],
                    ['GET|PUT /api/v1/taxas-cartao', 'Taxas de cartão'],
                ] as [$endpoint, $desc])
                <div class="flex items-start gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0 mt-1.5"></span>
                    <div>
                        <code class="text-xs text-stone-400 block">{{ $endpoint }}</code>
                        <span class="text-stone-600">{{ $desc }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </main>

    <footer class="text-center py-8 text-xs text-stone-400">
        LC Estética e Saúde Integrativa &mdash; Sistema de Gestão &copy; {{ date('Y') }}
    </footer>

</body>
</html>
