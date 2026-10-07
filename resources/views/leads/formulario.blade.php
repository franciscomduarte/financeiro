<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fale com a {{ $clinica->nome }}</title>
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
        @if ($enviado)
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <h1 class="mt-4 text-xl font-semibold text-stone-900">Recebemos seu contato!</h1>
                <p class="mt-2 text-sm text-stone-500">A equipe da {{ $clinica->nome }} vai falar com você pelo WhatsApp em breve.</p>
            </div>
        @else
            <h1 class="text-xl font-semibold text-stone-900">Quer agendar uma avaliação?</h1>
            <p class="mt-1 text-sm text-stone-500">Deixe seu contato e a equipe da {{ $clinica->nome }} fala com você pelo WhatsApp.</p>

            <form method="POST" action="{{ route('leads.formulario.enviar', $clinica->slug) }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="origem" value="{{ $origem }}">
                <div class="hidden" aria-hidden="true"><label>Site <input type="text" name="site" tabindex="-1" autocomplete="off"></label></div>

                <div>
                    <label for="nome" class="label">Seu nome</label>
                    <input id="nome" name="nome" type="text" required maxlength="150" autocomplete="name" value="{{ old('nome') }}" class="input">
                    @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="telefone" class="label">WhatsApp com DDD</label>
                    <input id="telefone" name="telefone" type="tel" inputmode="tel" required maxlength="20" autocomplete="tel" placeholder="(61) 99999-0000" value="{{ old('telefone') }}" class="input">
                    @error('telefone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="label">E-mail (opcional)</label>
                    <input id="email" name="email" type="email" inputmode="email" maxlength="150" autocomplete="email" value="{{ old('email') }}" class="input">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @if ($procedimentos->isNotEmpty())
                    <div>
                        <label for="procedimento_id" class="label">Tem interesse em algum procedimento?</label>
                        <select id="procedimento_id" name="procedimento_id" class="input">
                            <option value="">Ainda não sei / quero uma avaliação</option>
                            @foreach ($procedimentos as $p)
                                <option value="{{ $p->id }}" @selected((string) old('procedimento_id') === (string) $p->id)>{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="mensagem" class="label">Quer contar algo? (opcional)</label>
                    <textarea id="mensagem" name="mensagem" rows="3" maxlength="1000" class="input" placeholder="Ex.: Tenho manchas no rosto e queria saber o melhor tratamento.">{{ old('mensagem') }}</textarea>
                </div>
                <label class="flex items-start gap-3 text-sm text-stone-600">
                    <input type="checkbox" name="consentimento" value="1" required @checked(old('consentimento')) class="mt-0.5 h-5 w-5 shrink-0 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                    <span>Autorizo a {{ $clinica->nome }} a usar estes dados para falar comigo sobre atendimento.</span>
                </label>
                @error('consentimento') <p class="field-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn-primary w-full">Quero ser contatada</button>
            </form>
        @endif
    </div>
    <p class="mt-6 text-center text-xs text-stone-400">Seus dados ficam só com a clínica e não são compartilhados.</p>
</main>
</body>
</html>
