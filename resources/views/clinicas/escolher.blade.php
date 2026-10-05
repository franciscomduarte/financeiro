<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escolha a clínica · {{ config('app.name') }}</title>
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
    <div class="card w-full max-w-sm p-6">
        <h1 class="text-xl font-semibold text-stone-900">Escolha a clínica</h1>
        <p class="mt-1 text-sm text-stone-500">Você tem acesso a mais de uma. Escolha em qual quer entrar agora.</p>

        <ul class="mt-5 space-y-2">
            @foreach ($clinicas as $c)
                <li>
                    <form method="POST" action="{{ route('clinicas.ativar', $c->id) }}">
                        @csrf
                        <button type="submit"
                                class="group flex min-h-[52px] w-full items-center gap-3 rounded-xl border border-stone-200 px-3 text-left transition-colors hover:border-rose-300 hover:bg-rose-50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-sm font-semibold text-stone-600 group-hover:bg-surface" aria-hidden="true">{{ mb_strtoupper(mb_substr($c->nome, 0, 1)) }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-stone-900">{{ $c->nome }}</span>
                            @if ($c->estaBloqueada())
                                <span class="badge bg-red-50 text-red-700">Bloqueada</span>
                            @else
                                <svg class="h-4 w-4 shrink-0 text-stone-400 group-hover:text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            @endif
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn-ghost w-full">Sair</button>
        </form>
    </div>
</main>
</body>
</html>
