<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'LC Estética — Gestão' }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        class="fixed md:static inset-y-0 left-0 z-40 w-60 bg-white border-r border-stone-100 flex flex-col transform transition-transform duration-200 ease-out"
    >
        {{-- Logo --}}
        <div class="h-14 px-4 flex items-center gap-3 border-b border-stone-100 shrink-0">
            <div class="w-8 h-8 rounded-xl bg-rose-600 flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-stone-800 leading-tight truncate">LC Estética</p>
                <p class="text-xs text-stone-400 leading-tight truncate">Saúde Integrativa</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

            <p class="text-xs font-semibold text-stone-400 uppercase tracking-widest px-2 mb-2">Financeiro</p>

            <a href="{{ route('transacoes.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                   {{ request()->routeIs('transacoes.*') ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('transacoes.*') ? 'text-rose-600' : 'text-stone-400 group-hover:text-stone-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                Transações
            </a>

            <a href="{{ route('taxas-cartao.index') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                   {{ request()->routeIs('taxas-cartao.*') ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('taxas-cartao.*') ? 'text-rose-600' : 'text-stone-400 group-hover:text-stone-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                </svg>
                Taxas de Cartão
            </a>

            <div class="pt-4">
                <p class="text-xs font-semibold text-stone-400 uppercase tracking-widest px-2 mb-2">Fornecedores</p>
                <a href="{{ route('web.fornecedores') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                          {{ request()->routeIs('web.fornecedores') ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800' }}">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.fornecedores') ? 'text-rose-600' : 'text-stone-400 group-hover:text-stone-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Fornecedores
                </a>
                <a href="{{ route('web.contratos') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                          {{ request()->routeIs('web.contratos') ? 'bg-rose-50 text-rose-700' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800' }}">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('web.contratos') ? 'text-rose-600' : 'text-stone-400 group-hover:text-stone-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    Contratos
                </a>
            </div>

            <div class="pt-4">
                <p class="text-xs font-semibold text-stone-300 uppercase tracking-widest px-2 mb-2">Em breve</p>
                <span class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-stone-300 cursor-not-allowed select-none">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    Dashboard
                    <span class="ml-auto text-xs bg-stone-100 text-stone-300 px-1.5 py-0.5 rounded-md font-normal">Fase 3</span>
                </span>
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
</body>
</html>
