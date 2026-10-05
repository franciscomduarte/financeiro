<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fafaf9" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f0e0d" media="(prefers-color-scheme: dark)">
    <title>{{ $title ?? 'Início' }} · {{ $clinicaAtual?->nome ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-stone-50 text-stone-800 font-sans antialiased">

<div class="flex h-dvh overflow-hidden"
     x-data="{
        menuAberto: false,
        desktop: window.matchMedia('(min-width: 768px)').matches,
        recolhido: (() => { try { return localStorage.getItem('menu-recolhido') === '1' } catch (e) { return false } })(),
        alternarMenu() {
            this.recolhido = ! this.recolhido;
            try { localStorage.setItem('menu-recolhido', this.recolhido ? '1' : '0') } catch (e) {}
        },
     }"
     x-init="window.matchMedia('(min-width: 768px)').addEventListener('change', e => { desktop = e.matches; menuAberto = false })"
     @keydown.escape.window="menuAberto = false">

    {{-- Fundo escurecido do menu no celular --}}
    <div x-show="menuAberto" x-cloak x-transition.opacity @click="menuAberto = false"
         class="fixed inset-0 z-30 bg-black/40 md:hidden" aria-hidden="true"></div>

    {{-- Menu lateral --}}
    <aside :class="[menuAberto ? 'translate-x-0' : '-translate-x-full md:translate-x-0', recolhido ? 'md:w-[72px]' : 'md:w-64']"
           class="fixed md:static inset-y-0 left-0 z-40 w-72 md:w-64 shrink-0 bg-surface border-r border-stone-200/70 transform transition-[transform,width] duration-200 ease-out">
        <x-menu-lateral />
    </aside>

    {{-- Área principal --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Barra superior (celular: botão do menu + título) --}}
        <header class="md:hidden h-14 bg-surface/90 backdrop-blur border-b border-stone-200/70 flex items-center gap-2 px-2 shrink-0">
            <button type="button" @click="menuAberto = true" aria-label="Abrir menu"
                    class="w-11 h-11 flex items-center justify-center rounded-lg text-stone-600 hover:bg-stone-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <span class="min-w-0 truncate text-sm font-semibold text-stone-900">{{ $title ?? 'Início' }}</span>
        </header>

        {{-- Teste grátis e confirmação de e-mail --}}
        <x-avisos-clinica />

        {{-- Teste encerrado: aviso quando uma ação tenta gravar (evento do Livewire) --}}
        <div x-data="{ show: false }" x-on:clinica-somente-leitura.window="show = true; clearTimeout($el._t); $el._t = setTimeout(() => show = false, 6000)"
             x-show="show" x-cloak x-transition.opacity class="fixed top-4 right-4 left-4 sm:left-auto z-50 sm:max-w-sm" role="alert">
            <div class="bg-surface border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl text-sm">
                <strong>Modo somente leitura.</strong> O teste grátis terminou. Fale com a gente para assinar e voltar a cadastrar.
            </div>
        </div>

        {{-- Mensagens de retorno (sessão). Classes usadas: border-emerald-200 text-emerald-800 bg-emerald-100 text-emerald-600 border-red-200 text-red-800 bg-red-100 text-red-600 --}}
        @foreach (['success' => ['emerald', 'M4.5 12.75l6 6 9-13.5', 4000], 'error' => ['red', 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z', 6000]] as $chave => [$cor, $icone, $tempo])
            @if (session($chave))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, {{ $tempo }})" x-transition.opacity
                     class="fixed top-4 right-4 left-4 sm:left-auto z-50 sm:max-w-sm" role="{{ $chave === 'error' ? 'alert' : 'status' }}">
                    <div class="bg-surface border border-{{ $cor }}-200 text-{{ $cor }}-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                        <span class="w-6 h-6 rounded-full bg-{{ $cor }}-100 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-{{ $cor }}-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}" /></svg>
                        </span>
                        <span class="flex-1">{{ session($chave) }}</span>
                        <button type="button" @click="show = false" class="text-stone-400 hover:text-stone-600" aria-label="Fechar">✕</button>
                    </div>
                </div>
            @endif
        @endforeach

        {{-- Conteúdo --}}
        <main class="flex-1 overflow-y-auto">
            <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
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
