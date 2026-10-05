<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sem clínica vinculada</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-2xl border border-stone-100 bg-surface p-6 text-center shadow-sm">
        <h1 class="text-lg font-bold text-stone-900">Sem clínica vinculada</h1>
        <p class="mt-2 text-sm text-stone-500">Sua conta ainda não tem acesso a nenhuma clínica. Peça ao administrador da clínica para incluir você.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="min-h-[44px] rounded-xl border border-stone-200 px-5 text-sm font-semibold text-stone-700 hover:bg-stone-50">Sair</button>
        </form>
    </div>
</body>
</html>
