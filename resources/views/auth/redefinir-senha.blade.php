<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova senha · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface font-sans text-stone-800 antialiased">

<div class="flex min-h-screen">

    {{-- ─── Painel da marca (só no desktop) ─── --}}
    <aside class="relative hidden overflow-hidden bg-rose-600 text-white lg:flex lg:w-[440px] lg:shrink-0 lg:flex-col xl:w-[480px]">
        <svg class="absolute inset-0 h-full w-full opacity-10" aria-hidden="true">
            <defs>
                <pattern id="pontos" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="3" cy="3" r="1.5" fill="white" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#pontos)" />
        </svg>

        <div class="relative flex h-full flex-col px-10 py-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3 self-start rounded-xl">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg font-semibold" aria-hidden="true">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 1)) }}</span>
                <span class="font-semibold">{{ config('app.name') }}</span>
            </a>

            <div class="my-auto py-12">
                <h1 class="text-3xl font-semibold leading-tight tracking-tight xl:text-4xl">Agenda, financeiro e estoque da sua clínica num só lugar.</h1>
                <p class="mt-3 max-w-sm text-base leading-relaxed text-white/80">Menos planilha, mais tempo para cuidar dos seus pacientes.</p>

                <ul class="mt-8 space-y-3 text-sm text-white/90">
                    @foreach (['Agenda com confirmação por WhatsApp e e-mail', 'Financeiro com taxas de cartão e impostos calculados', 'Cobranças recorrentes pelo Asaas', 'Alertas de contas, contratos e alvarás vencendo', 'Estoque com controle de lotes e validade'] as $item)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-xs text-white/60">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </aside>

    {{-- ─── Formulário ─── --}}
    <main class="flex flex-1 items-start justify-center px-4 py-10 sm:items-center sm:px-6 sm:py-12">
        <div class="w-full max-w-sm">

            {{-- Logo (só no celular) --}}
            <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-600 text-base font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 1)) }}</span>
                <span class="font-semibold text-stone-900">{{ config('app.name') }}</span>
            </a>

            <div class="mb-8">
                <h2 class="text-2xl font-semibold tracking-tight text-stone-900">Crie uma nova senha</h2>
                <p class="mt-1 text-sm text-stone-500">Use pelo menos 8 caracteres. Depois é só entrar com ela.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-3.5" role="alert">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <div class="text-sm text-red-700">
                        <p>{{ $errors->first() }}</p>
                        @error('token')
                            <a href="{{ route('password.request') }}" class="mt-1 inline-flex min-h-[44px] items-center font-medium underline">Pedir um novo link</a>
                        @enderror
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="label">E-mail</label>
                    <input id="email" name="email" type="email" inputmode="email" autocomplete="email" required
                           value="{{ old('email', request()->query('email')) }}" class="input @error('email') border-red-300 @enderror" placeholder="Ex.: voce@clinica.com.br">
                </div>

                <div>
                    <label for="password" class="label">Nova senha</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           class="input @error('password') border-red-300 @enderror" placeholder="Mínimo de 8 caracteres">
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirme a nova senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                           class="input" placeholder="Repita a nova senha">
                </div>

                <button type="submit" class="btn-primary w-full">Salvar nova senha</button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-500">
                <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center font-medium text-rose-600 hover:text-rose-700">Voltar para o login</a>
            </p>
        </div>
    </main>

</div>
</body>
</html>
