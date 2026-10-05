<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica bloqueada</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl border border-stone-100 bg-surface p-6 text-center shadow-sm">
        <h1 class="text-lg font-bold text-stone-900">{{ $clinica->nome }} está bloqueada</h1>
        <p class="mt-2 text-sm text-stone-500">O acesso a esta clínica está suspenso. Entre em contato com o suporte.</p>
        <div class="mt-6 flex flex-col gap-2">
            @if (auth()->user()->clinicas()->count() > 1)
                <a href="{{ route('clinicas.escolher') }}" class="min-h-[44px] rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700">Escolher outra clínica</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="min-h-[44px] w-full rounded-xl border border-stone-200 px-5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Sair</button>
            </form>
        </div>
    </div>
</body>
</html>
