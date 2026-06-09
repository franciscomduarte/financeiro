<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'LC Estética — Gestão' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-stone-50 font-sans antialiased">

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">

    {{-- ─── Sidebar overlay (mobile) ──────────────────── --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/30 z-30 md:hidden"
    ></div>

    {{-- ─── Sidebar ───────────────────────────────────── --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed md:static inset-y-0 left-0 z-40 w-60 border-r border-stone-100 flex flex-col transform transition-transform duration-200 ease-out"
        style="background: #ffffff"
    >
        {{-- Logo --}}
        <div class="h-14 px-4 flex items-center gap-3 border-b border-stone-100 shrink-0">
            <div class="w-8 h-8 rounded-xl bg-rose-600 flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-stone-800 leading-tight truncate" style="font-family: 'Playfair Display', serif">LC Estética</p>
                <p class="text-xs text-stone-400 leading-tight truncate">Saúde Integrativa</p>
            </div>
        </div>

        {{-- Nav --}}
        @php
            $zonaClinica  = request()->routeIs('dashboard', 'pacientes.*', 'cobrancas.*', 'transacoes.*');
            $zonaAgenda   = request()->routeIs('agenda.*');
            $zonaEstoque  = request()->routeIs('estoque.*');
            $zonaGestao   = request()->routeIs('web.*', 'taxas-cartao.*');
        @endphp
        <nav class="flex-1 px-3 py-4 overflow-y-auto">

            {{-- ══════════════════════════════════════
                 ZONA CLÍNICA — operação diária
            ══════════════════════════════════════ --}}
            <div x-data="{ open: {{ $zonaClinica ? 'true' : 'false' }} }" class="mb-1">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-2 py-1 mb-1 rounded-md hover:bg-rose-50/60 transition-colors group">
                    <span class="text-rose-400 group-hover:text-rose-500 transition-colors"
                          style="font-family: 'Playfair Display', serif; font-style: italic; font-size: 10px; letter-spacing: 0.14em;">Clínica</span>
                    <svg class="w-2.5 h-2.5 text-rose-300 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-0.5">

                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('dashboard') ? 'bg-rose-50 text-rose-800' : 'text-stone-600 hover:bg-rose-50/50 hover:text-rose-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-rose-500' : 'text-stone-400 group-hover:text-rose-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('pacientes.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('pacientes.*') ? 'bg-rose-50 text-rose-800' : 'text-stone-600 hover:bg-rose-50/50 hover:text-rose-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('pacientes.*') ? 'text-rose-500' : 'text-stone-400 group-hover:text-rose-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        Pacientes
                    </a>

                    <a href="{{ route('cobrancas.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('cobrancas.*') ? 'bg-rose-50 text-rose-800' : 'text-stone-600 hover:bg-rose-50/50 hover:text-rose-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('cobrancas.*') ? 'text-rose-500' : 'text-stone-400 group-hover:text-rose-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        Cobranças
                    </a>

                    <a href="{{ route('transacoes.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('transacoes.*') ? 'bg-rose-50 text-rose-800' : 'text-stone-600 hover:bg-rose-50/50 hover:text-rose-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('transacoes.*') ? 'text-rose-500' : 'text-stone-400 group-hover:text-rose-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                        Transações
                    </a>
                </div>
            </div>

            {{-- ── Separador gradiente entre zonas ── --}}
            <div class="mx-1 my-3.5 h-px bg-gradient-to-r from-rose-200 via-violet-200 to-violet-200"></div>

            {{-- ══════════════════════════════════════
                 ZONA AGENDA
            ══════════════════════════════════════ --}}
            <div x-data="{ open: {{ $zonaAgenda ? 'true' : 'false' }} }" class="mb-1">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-2 py-1 mb-1 rounded-md hover:bg-violet-50/60 transition-colors group">
                    <span class="text-violet-400 group-hover:text-violet-500 transition-colors"
                          style="font-family: 'Playfair Display', serif; font-style: italic; font-size: 10px; letter-spacing: 0.14em;">Agenda</span>
                    <svg class="w-2.5 h-2.5 text-violet-300 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-0.5">

                    <a href="{{ route('agenda.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('agenda.index') ? 'bg-violet-50 text-violet-800' : 'text-stone-600 hover:bg-violet-50/50 hover:text-violet-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('agenda.index') ? 'text-violet-500' : 'text-stone-400 group-hover:text-violet-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        Agendamentos
                    </a>

                    <a href="{{ route('agenda.configuracao') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('agenda.configuracao') ? 'bg-violet-50 text-violet-800' : 'text-stone-600 hover:bg-violet-50/50 hover:text-violet-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('agenda.configuracao') ? 'text-violet-500' : 'text-stone-400 group-hover:text-violet-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Configurações
                    </a>
                </div>
            </div>

            {{-- ── Separador gradiente entre zonas ── --}}
            <div class="mx-1 my-3.5 h-px bg-gradient-to-r from-violet-200 via-stone-200 to-emerald-200"></div>

            {{-- ══════════════════════════════════════
                 ZONA ESTOQUE
            ══════════════════════════════════════ --}}
            <div x-data="{ open: {{ $zonaEstoque ? 'true' : 'false' }} }" class="mb-1">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-2 py-1 mb-1 rounded-md hover:bg-emerald-50/60 transition-colors group">
                    <span class="text-emerald-400 group-hover:text-emerald-500 transition-colors"
                          style="font-family: 'Playfair Display', serif; font-style: italic; font-size: 10px; letter-spacing: 0.14em;">Estoque</span>
                    <svg class="w-2.5 h-2.5 text-emerald-300 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-0.5">

                    <a href="{{ route('estoque.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('estoque.index') ? 'bg-emerald-50 text-emerald-800' : 'text-stone-600 hover:bg-emerald-50/50 hover:text-emerald-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('estoque.index') ? 'text-emerald-500' : 'text-stone-400 group-hover:text-emerald-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('estoque.produtos') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('estoque.produtos') ? 'bg-emerald-50 text-emerald-800' : 'text-stone-600 hover:bg-emerald-50/50 hover:text-emerald-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('estoque.produtos') ? 'text-emerald-500' : 'text-stone-400 group-hover:text-emerald-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Produtos
                    </a>

                    <a href="{{ route('estoque.movimentacoes') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('estoque.movimentacoes') ? 'bg-emerald-50 text-emerald-800' : 'text-stone-600 hover:bg-emerald-50/50 hover:text-emerald-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('estoque.movimentacoes') ? 'text-emerald-500' : 'text-stone-400 group-hover:text-emerald-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                        Movimentações
                    </a>
                </div>
            </div>

            {{-- ── Separador gradiente entre zonas ── --}}
            <div class="mx-1 my-3.5 h-px bg-gradient-to-r from-emerald-200 via-stone-200 to-slate-200"></div>

            {{-- ══════════════════════════════════════
                 ZONA GESTÃO — back-office
            ══════════════════════════════════════ --}}
            <div x-data="{ open: {{ $zonaGestao ? 'true' : 'false' }} }" class="mb-1">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-2 py-1 mb-1 rounded-md hover:bg-slate-50 transition-colors group">
                    <span class="text-slate-400 font-semibold group-hover:text-slate-500 transition-colors"
                          style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.18em;">Gestão</span>
                    <svg class="w-2.5 h-2.5 text-slate-300 transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-0.5">

                    <a href="{{ route('web.fornecedores') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('web.fornecedores') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.fornecedores') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Fornecedores
                    </a>

                    <a href="{{ route('web.contratos') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('web.contratos') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.contratos') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Contratos
                    </a>

                    <a href="{{ route('web.contas-consumo') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('web.contas-consumo') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.contas-consumo') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        Contas de Consumo
                    </a>

                    <a href="{{ route('web.obrigacoes-fiscais') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('web.obrigacoes-fiscais') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.obrigacoes-fiscais') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
                        </svg>
                        Obrigações Fiscais
                    </a>

                    <a href="{{ route('web.documentos') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('web.documentos') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.documentos') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Documentos
                    </a>

                    <a href="{{ route('web.relatorio') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('web.relatorio') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.relatorio') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Relatório
                    </a>

                    <a href="{{ route('taxas-cartao.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('taxas-cartao.*') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('taxas-cartao.*') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        Taxas de Cartão
                    </a>
                </div>
            </div>

            {{-- ── Conta (bottom) ── --}}
            <div class="mt-4 pt-3 border-t border-stone-100 space-y-0.5">
                <a href="{{ route('minha-conta') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                          {{ request()->routeIs('minha-conta') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('minha-conta') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    Minha Conta
                </a>
                @if (Auth::user()?->isAdmin())
                    <a href="{{ route('admin.usuarios') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                              {{ request()->routeIs('admin.*') ? 'bg-slate-100 text-slate-800' : 'text-stone-500 hover:bg-slate-50 hover:text-slate-700' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.*') ? 'text-slate-500' : 'text-stone-400 group-hover:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        Usuários
                    </a>
                @endif
            </div>
        </nav>

        {{-- User section --}}
        <div class="px-3 py-3 border-t border-stone-100 shrink-0">
            <div class="flex items-center gap-2.5 px-2 py-2">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center shrink-0">
                    <span class="text-xs font-bold text-white">{{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-stone-700 truncate leading-tight">{{ Auth::user()?->name }}</p>
                    <p class="text-xs text-stone-400 truncate leading-tight">{{ Auth::user()?->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sair"
                        class="p-1.5 text-stone-400 hover:text-stone-600 hover:bg-stone-100 transition-colors rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ─── Main area ──────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="h-14 bg-white border-b border-stone-100 flex items-center px-4 md:px-6 shrink-0 gap-4">
            {{-- Mobile menu button --}}
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-1.5 text-stone-500 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            {{-- Breadcrumb/title --}}
            <div class="flex items-center gap-2 text-sm">
                <span class="text-stone-400">LC Estética</span>
                <svg class="w-3.5 h-3.5 text-stone-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
                <span class="font-medium text-stone-700">{{ $title ?? 'Dashboard' }}</span>
            </div>
        </header>

        {{-- Session flash messages --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="fixed top-4 right-4 z-50 max-w-sm pointer-events-none">
                <div class="bg-white border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm pointer-events-auto">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed top-4 right-4 z-50 max-w-sm">
                <div class="bg-white border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                    <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        {{-- Scrollable page content --}}
        <main class="flex-1 overflow-y-auto">
            <div class="p-6">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>

    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
