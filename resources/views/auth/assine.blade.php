<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Crie a conta da sua clínica e use o {{ config('app.name') }} grátis por {{ config('clinica.dias_teste') }} dias.">
    <title>Assine já — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-stone-50 font-sans antialiased">

<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- ─── Benefícios (no celular aparece resumido acima do formulário) ─── --}}
    <aside class="bg-rose-600 text-white px-6 py-8 lg:w-[440px] lg:shrink-0 lg:px-10 lg:py-12 lg:flex lg:flex-col">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-bold">{{ mb_substr(config('app.name'), 0, 1) }}</span>
            <span class="font-semibold">{{ config('app.name') }}</span>
        </a>

        <h1 class="text-2xl lg:text-3xl font-bold leading-tight mt-6 lg:mt-auto">
            {{ config('clinica.dias_teste') }} dias grátis para organizar sua clínica
        </h1>
        <p class="text-rose-100 text-sm mt-2">Sem cartão de crédito. Seus dados ficam só com você.</p>

        <ul class="hidden lg:block space-y-3 mt-8 mb-auto text-sm text-rose-50">
            @foreach (['Agenda com confirmação por WhatsApp e e-mail', 'Financeiro com taxas de cartão e impostos calculados', 'Cobranças recorrentes pelo Asaas', 'Alertas de contas, contratos e alvarás vencendo', 'Estoque com controle de lotes e validade'] as $item)
                <li class="flex items-start gap-2.5">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    {{ $item }}
                </li>
            @endforeach
        </ul>
    </aside>

    {{-- ─── Formulário ─── --}}
    <main class="flex-1 flex items-start lg:items-center justify-center px-4 py-8 sm:px-6">
        <div class="w-full max-w-md">
            <h2 class="text-xl font-bold text-stone-900">Crie a conta da sua clínica</h2>
            <p class="text-sm text-stone-500 mt-1">Leva menos de um minuto.</p>

            @if ($errors->any())
                <div class="mt-5 p-3.5 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('cadastro.store') }}" class="mt-6 space-y-4">
                @csrf

                @php($campo = 'w-full border border-stone-200 bg-white rounded-xl px-4 py-3 text-base sm:text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:border-transparent')

                <div>
                    <label for="clinica" class="block text-sm font-medium text-stone-700 mb-1.5">Nome da clínica</label>
                    <input id="clinica" name="clinica" type="text" required maxlength="150" autocomplete="organization"
                           value="{{ old('clinica') }}" class="{{ $campo }} @error('clinica') border-red-300 @enderror" placeholder="Ex.: Clínica Bem Estar">
                </div>

                <div>
                    <label for="nome" class="block text-sm font-medium text-stone-700 mb-1.5">Seu nome</label>
                    <input id="nome" name="nome" type="text" required maxlength="150" autocomplete="name"
                           value="{{ old('nome') }}" class="{{ $campo }} @error('nome') border-red-300 @enderror">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">E-mail</label>
                        <input id="email" name="email" type="email" required maxlength="150" autocomplete="email" inputmode="email"
                               value="{{ old('email') }}" class="{{ $campo }} @error('email') border-red-300 @enderror" placeholder="voce@clinica.com">
                    </div>
                    <div>
                        <label for="celular" class="block text-sm font-medium text-stone-700 mb-1.5">Celular (WhatsApp)</label>
                        <input id="celular" name="celular" type="tel" required maxlength="30" autocomplete="tel" inputmode="tel"
                               value="{{ old('celular') }}" class="{{ $campo }} @error('celular') border-red-300 @enderror" placeholder="(61) 99999-0000">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-stone-700 mb-1.5">Senha</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                               class="{{ $campo }} @error('password') border-red-300 @enderror">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-stone-700 mb-1.5">Repita a senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                               class="{{ $campo }}">
                    </div>
                </div>
                <p class="text-xs text-stone-500 -mt-2">Mínimo de 8 caracteres, com letras e números.</p>

                {{-- Armadilha para robôs: invisível para pessoas --}}
                <div class="hidden" aria-hidden="true">
                    <label for="site">Site</label>
                    <input id="site" name="site" type="text" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit"
                        class="w-full min-h-[48px] bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white px-4 py-3 rounded-xl text-base sm:text-sm font-semibold transition-colors shadow-sm shadow-rose-200">
                    Começar meu teste grátis
                </button>
            </form>

            <p class="text-center text-sm text-stone-500 mt-6">
                Já tem conta?
                <a href="{{ route('login') }}" class="text-rose-600 font-medium hover:underline">Entrar</a>
            </p>
        </div>
    </main>
</div>
</body>
</html>
