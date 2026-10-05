<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirme seu e-mail — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-stone-50 font-sans antialiased">
<main class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-2xl border border-stone-100 shadow-sm p-6 sm:p-8 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-amber-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
        </div>
        <h1 class="text-xl font-bold text-stone-900 mt-4">Confirme seu e-mail</h1>
        <p class="text-sm text-stone-600 mt-2">
            O teste grátis terminou e, para continuar acessando os dados da clínica, precisamos confirmar o e-mail
            <strong>{{ auth()->user()->email }}</strong>. Abra o link que enviamos para ele.
        </p>

        @if (session('success'))
            <p class="mt-4 text-sm text-emerald-700 bg-emerald-50 rounded-xl px-3 py-2">{{ session('success') }}</p>
        @endif
        @error('email')
            <p class="mt-4 text-sm text-red-700 bg-red-50 rounded-xl px-3 py-2">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('verificacao.reenviar') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full min-h-[48px] bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">
                Reenviar o link
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full min-h-[44px] text-sm text-stone-500 hover:text-stone-700">Sair</button>
        </form>
    </div>
</main>
</body>
</html>
