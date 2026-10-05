<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Como foi seu atendimento? · {{ $clinica->nome }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-stone-50 font-sans text-stone-800 antialiased">
<main class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
    <div class="mb-6 flex items-center justify-center gap-3">
        @if ($clinica->logoUrl())
            <img src="{{ $clinica->logoUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">
        @endif
        <p class="text-lg font-semibold text-stone-900">{{ $clinica->nome }}</p>
    </div>

    <div class="card p-6 sm:p-8">
        @if ($pesquisa->respondida_em)
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <h1 class="mt-4 text-xl font-semibold text-stone-900">Obrigado pela resposta!</h1>
                <p class="mt-2 text-sm text-stone-500">Sua opinião ajuda a {{ $clinica->nome }} a cuidar cada vez melhor de você.</p>
            </div>
        @else
            <h1 class="text-xl font-semibold text-stone-900">Olá, {{ explode(' ', trim($pesquisa->paciente?->nome ?? ''))[0] }}! Como foi seu atendimento?</h1>
            <p class="mt-1 text-sm text-stone-500">De 0 a 10, quanto você recomendaria a {{ $clinica->nome }} para um amigo?</p>

            <form method="POST" action="{{ route('avaliacao.responder', $pesquisa->token) }}" class="mt-6 space-y-5" x-data="{ nota: @js(old('nota')) }">
                @csrf
                <fieldset>
                    <legend class="sr-only">Nota de 0 a 10</legend>
                    <div class="grid grid-cols-6 gap-2 sm:grid-cols-11">
                        @foreach (range(0, 10) as $n)
                            <label class="cursor-pointer">
                                <input type="radio" name="nota" value="{{ $n }}" class="peer sr-only" x-model="nota" @checked((string) old('nota') === (string) $n)>
                                <span class="flex h-11 items-center justify-center rounded-xl border border-stone-200 text-sm font-semibold tabular-nums text-stone-700 transition-colors peer-checked:border-rose-600 peer-checked:bg-rose-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-rose-300">{{ $n }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-stone-400">
                        <span>Nada provável</span><span>Com certeza</span>
                    </div>
                    @error('nota') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
                <div>
                    <label for="comentario" class="label">Quer contar mais? (opcional)</label>
                    <textarea id="comentario" name="comentario" rows="3" maxlength="2000" class="input" placeholder="Ex.: Fui muito bem atendida, adorei o resultado.">{{ old('comentario') }}</textarea>
                    @error('comentario') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary w-full" :disabled="nota === null || nota === ''">Enviar avaliação</button>
            </form>
        @endif
    </div>
    <p class="mt-6 text-center text-xs text-stone-400">Suas respostas ficam só com a clínica.</p>
</main>
</body>
</html>
