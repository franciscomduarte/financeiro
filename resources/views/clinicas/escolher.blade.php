<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha a clínica</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl border border-stone-100 bg-surface p-6 shadow-sm">
        <h1 class="text-lg font-bold text-stone-900">Escolha a clínica</h1>
        <p class="mt-1 text-sm text-stone-500">Você tem acesso a mais de uma clínica.</p>

        <ul class="mt-5 space-y-2">
            @foreach ($clinicas as $c)
                <li>
                    <form method="POST" action="{{ route('clinicas.ativar', $c->id) }}">
                        @csrf
                        <button type="submit"
                                class="flex min-h-[48px] w-full items-center justify-between rounded-xl border border-stone-200 px-4 text-left text-sm font-semibold text-stone-800 hover:border-rose-300 hover:bg-rose-50 transition-colors">
                            {{ $c->nome }}
                            @if ($c->estaBloqueada())
                                <span class="text-xs font-medium text-red-600">Bloqueada</span>
                            @else
                                <span class="text-rose-500">→</span>
                            @endif
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-sm text-stone-400 hover:text-stone-600">Sair</button>
        </form>
    </div>
</body>
</html>
