<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Esqueci minha senha — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white font-sans antialiased">

<div class="flex min-h-screen">

    {{-- ─── Painel decorativo ─── --}}
    <div class="hidden lg:flex lg:w-[420px] xl:w-[480px] shrink-0 flex-col relative overflow-hidden bg-rose-600">
        <div class="absolute inset-0 opacity-10">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="dots" width="24" height="24" patternUnits="userSpaceOnUse">
                        <circle cx="3" cy="3" r="1.5" fill="white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#dots)" />
            </svg>
        </div>
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-rose-500/40 -translate-y-1/3 translate-x-1/3 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 rounded-full bg-rose-700/50 translate-y-1/3 -translate-x-1/4 blur-3xl"></div>
        <div class="relative z-10 flex flex-col h-full px-10 py-12">
            <div class="flex items-center gap-3 mb-auto">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-white font-semibold">{{ config('app.name') }}</p>
                    <p class="text-rose-200 text-xs">Saúde Integrativa</p>
                </div>
            </div>
            <div class="mb-auto">
                <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-3">Recupere seu acesso</h1>
                <p class="text-rose-100 text-sm leading-relaxed">
                    Informe seu e-mail e enviaremos um link para você criar uma nova senha.
                </p>
            </div>
            <p class="text-rose-200/60 text-xs">Sistema interno &copy; {{ date('Y') }}</p>
        </div>
    </div>

    {{-- ─── Formulário ─── --}}
    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-stone-50">
        <div class="w-full max-w-sm">

            {{-- Mobile logo --}}
            <div class="flex items-center justify-center gap-3 mb-10 lg:hidden">
                <div class="w-10 h-10 rounded-xl bg-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-stone-800">{{ config('app.name') }}</span>
            </div>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-stone-900">Esqueci minha senha</h2>
                <p class="text-stone-500 text-sm mt-1">Digite seu e-mail para receber o link de recuperação.</p>
            </div>

            @if (session('status'))
                <div class="mb-5 flex items-start gap-3 p-3.5 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <p class="text-sm text-emerald-700">{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 flex items-center gap-3 p-3.5 bg-red-50 border border-red-100 rounded-xl">
                    <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm text-red-700">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700 mb-2">E-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        class="w-full border border-stone-200 bg-white rounded-xl px-4 py-3 text-sm text-stone-800 placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:border-transparent transition @error('email') border-red-300 focus:ring-red-200 @enderror"
                        placeholder="seu@email.com"
                    >
                </div>

                <button type="submit"
                    class="w-full bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white px-4 py-3 rounded-xl text-sm font-semibold transition-colors shadow-sm shadow-rose-200">
                    Enviar link de recuperação
                </button>
            </form>

            <p class="text-center text-sm text-stone-500 mt-6">
                Lembrou a senha?
                <a href="{{ route('login') }}" class="text-rose-600 font-medium hover:text-rose-700 hover:underline">
                    Voltar ao login
                </a>
            </p>
        </div>
    </div>

</div>
</body>
</html>
