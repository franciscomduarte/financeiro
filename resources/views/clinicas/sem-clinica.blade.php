<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sem clínica vinculada · {{ config('app.name') }}</title>
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
    <div class="card w-full max-w-sm p-6 text-center sm:p-8">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
        </div>
        <h1 class="mt-4 text-xl font-semibold text-stone-900">Sem clínica vinculada</h1>
        <p class="mt-2 text-sm text-stone-500">Sua conta ainda não tem acesso a nenhuma clínica. Peça ao administrador da clínica para incluir você em Usuários.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="btn-secondary w-full">Sair</button>
        </form>
    </div>
</main>
</body>
</html>
