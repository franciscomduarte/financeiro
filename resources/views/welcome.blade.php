<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ config('app.name') }}: agenda, financeiro, cobranças, estoque e documentos da sua clínica em um só lugar. Teste grátis por {{ config('clinica.dias_teste') }} dias.">
    <title>{{ config('app.name') }} — gestão completa para clínicas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans antialiased text-stone-800">

@php($dias = config('clinica.dias_teste'))

{{-- ─── Topo ─── --}}
<header class="sticky top-0 z-30 bg-surface/90 backdrop-blur border-b border-stone-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0">
            <span class="w-9 h-9 shrink-0 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold">{{ mb_substr(config('app.name'), 0, 1) }}</span>
            <span class="font-semibold truncate">{{ config('app.name') }}</span>
        </a>
        <nav class="flex items-center gap-1 sm:gap-2 shrink-0">
            <a href="{{ route('login') }}" class="inline-flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium text-stone-600 hover:bg-stone-100">Entrar</a>
            <a href="{{ route('cadastro') }}" class="inline-flex items-center min-h-[44px] px-4 rounded-lg text-sm font-semibold bg-rose-600 hover:bg-rose-700 text-white">Assine já</a>
        </nav>
    </div>
</header>

{{-- ─── Destaque ─── --}}
<section class="bg-gradient-to-b from-rose-50 to-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-16 md:pt-24 md:pb-24 text-center">
        <span class="inline-block text-xs font-semibold uppercase tracking-wider text-rose-700 bg-rose-100 rounded-full px-3 py-1">Para clínicas de estética e saúde</span>
        <h1 class="mt-5 text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight leading-tight max-w-3xl mx-auto">
            A gestão da sua clínica, do agendamento ao caixa, num só lugar
        </h1>
        <p class="mt-5 text-base md:text-lg text-stone-600 max-w-2xl mx-auto">
            Agenda com lembretes por WhatsApp, financeiro com taxas e impostos calculados, cobranças automáticas,
            estoque e alertas de vencimento. Tudo funcionando no celular.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('cadastro') }}" class="inline-flex justify-center items-center min-h-[52px] px-7 rounded-xl text-base font-semibold bg-rose-600 hover:bg-rose-700 text-white shadow-lg shadow-rose-200">
                Testar grátis por {{ $dias }} dias
            </a>
            <a href="#recursos" class="inline-flex justify-center items-center min-h-[52px] px-7 rounded-xl text-base font-medium text-stone-700 bg-surface border border-stone-200 hover:border-stone-300">
                Ver recursos
            </a>
        </div>
        <p class="mt-4 text-sm text-stone-500">Sem cartão de crédito · Cancele quando quiser</p>
    </div>
</section>

{{-- ─── Recursos ─── --}}
<section id="recursos" class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-24">
    <h2 class="text-2xl md:text-3xl font-bold text-center">Tudo o que a rotina da clínica pede</h2>
    <p class="mt-3 text-stone-600 text-center max-w-2xl mx-auto">Menos planilhas e mensagens soltas, mais tempo para os pacientes.</p>

    @php($recursos = [
        ['Agenda inteligente', 'Calendário por profissional, bloqueios, intervalos e confirmações automáticas por WhatsApp e e-mail.', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['Financeiro sem surpresa', 'Entradas e saídas com taxa da maquininha e imposto estimado já descontados. Relatórios por período.', 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
        ['Cobranças automáticas', 'Mensalidades e parcelamentos pelo Asaas, com Pix e boleto e baixa automática quando o paciente paga.', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
        ['Alertas de vencimento', 'Resumo diário de contas, guias, contratos e alvarás que vencem — por e-mail e WhatsApp.', 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
        ['Estoque com validade', 'Lotes, frascos abertos e estoque mínimo. Avisos antes de faltar ou vencer.', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
        ['Equipe e segurança', 'Cada pessoa com seu acesso, administradores e usuários. Dados da clínica isolados e protegidos.', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
    ])
    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($recursos as [$titulo, $texto, $icone])
            <div class="rounded-2xl border border-stone-100 bg-stone-50/60 p-6">
                <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}" /></svg>
                </div>
                <h3 class="mt-4 font-semibold text-lg">{{ $titulo }}</h3>
                <p class="mt-2 text-sm text-stone-600 leading-relaxed">{{ $texto }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ─── Como funciona ─── --}}
<section class="bg-stone-50 border-y border-stone-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-20">
        <h2 class="text-2xl md:text-3xl font-bold text-center">Comece hoje, em três passos</h2>
        <ol class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Crie sua conta', 'Nome da clínica, seu e-mail e uma senha. Pronto: o sistema já abre.'],
                ['Configure do seu jeito', 'Logo, profissionais, procedimentos, taxas da maquininha e WhatsApp.'],
                ['Use por ' . $dias . ' dias grátis', 'Agende, lance e cobre de verdade. Gostou? É só assinar.'],
            ] as $i => [$titulo, $texto])
                <li class="bg-surface rounded-2xl border border-stone-100 p-6">
                    <span class="w-8 h-8 rounded-full bg-rose-600 text-white text-sm font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <h3 class="mt-4 font-semibold">{{ $titulo }}</h3>
                    <p class="mt-1.5 text-sm text-stone-600">{{ $texto }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ─── Chamada final ─── --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-16 md:py-24">
    <div class="rounded-3xl bg-rose-600 text-white px-6 py-12 md:px-12 text-center">
        <h2 class="text-2xl md:text-3xl font-bold">Vamos organizar sua clínica?</h2>
        <p class="mt-3 text-rose-100">{{ $dias }} dias grátis, sem cartão de crédito.</p>
        <a href="{{ route('cadastro') }}" class="mt-8 inline-flex justify-center items-center min-h-[52px] px-8 rounded-xl text-base font-semibold bg-surface text-rose-700 hover:bg-rose-50">
            Criar minha conta grátis
        </a>
    </div>
</section>

<footer class="border-t border-stone-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row gap-3 items-center justify-between text-sm text-stone-500">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        <div class="flex gap-4">
            @if ($whatsapp = \App\Support\Plataforma::whatsappLink('Olá! Quero saber mais sobre o ' . config('app.name') . '.'))
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="hover:text-stone-700">WhatsApp</a>
            @endif
            @if ($email = \App\Support\Plataforma::email())
                <a href="mailto:{{ $email }}" class="hover:text-stone-700">{{ $email }}</a>
            @endif
            <a href="{{ route('login') }}" class="hover:text-stone-700">Entrar</a>
        </div>
    </div>
</footer>
</body>
</html>
