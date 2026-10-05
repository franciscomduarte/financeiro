<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirme seu e-mail · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-stone-50 font-sans text-stone-800 antialiased">
<main class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
    <div class="mb-6 flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-600 text-base font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 1)) }}</span>
        <span class="font-semibold text-stone-900">{{ config('app.name') }}</span>
    </div>
    <div class="card w-full max-w-md p-6 text-center sm:p-8">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
        </div>
        <h1 class="mt-4 text-xl font-semibold text-stone-900">Confirme seu e-mail</h1>
        <p class="mt-2 text-sm text-stone-600">
            Seu teste grátis terminou. Para continuar acessando os dados da clínica, confirme o e-mail
            <strong class="font-semibold text-stone-900">{{ auth()->user()->email }}</strong> pelo link que enviamos.
        </p>
        <p class="mt-2 text-sm text-stone-500">Não achou? Olhe também a caixa de spam.</p>

        @if (session('success'))
            <p class="mt-4 rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-700" role="status">{{ session('success') }}</p>
        @endif
        @error('email')
            <p class="mt-4 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('verificacao.reenviar') }}" class="mt-6">
            @csrf
            <button type="submit" class="btn-primary w-full">Reenviar o link</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn-ghost w-full">Sair</button>
        </form>
    </div>
</main>
</body>
</html>
