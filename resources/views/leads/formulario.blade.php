<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fale com a {{ $clinica->nome }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($googleAds = $clinica->googleAdsId())
        {{-- Tag do Google Ads da clínica (Dados da clínica › Página de contato) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAds }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($googleAds));
        </script>
        @if ($enviado && ($conversao = $clinica->googleAdsConversao()))
            <script>
                gtag('event', 'conversion', {'send_to': @json($conversao)});
            </script>
        @endif
        @if (! $enviado && ($conversaoWhatsapp = $clinica->googleAdsConversaoWhatsapp()))
            <script>
                // Clique em "Chamar no WhatsApp" conta como conversão (o link abre em outra aba, a página continua aberta)
                document.addEventListener('click', function (e) {
                    if (e.target.closest('[data-whatsapp-contato]')) {
                        gtag('event', 'conversion', {'send_to': @json($conversaoWhatsapp)});
                    }
                });
            </script>
        @endif
    @endif
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
            @if ($whatsapp = $clinica->linkWhatsappContato("Olá! Vim pelo site e quero agendar uma avaliação."))
                <p class="mt-1 text-sm text-stone-500">Chame a equipe da {{ $clinica->nome }} no WhatsApp agora ou deixe seu contato que a gente fala com você.</p>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" data-whatsapp-contato
                   class="mt-5 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] px-4 text-base font-semibold text-white shadow-sm transition-colors hover:bg-[#1ebe5b] focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91A9.86 9.86 0 0012.04 2zm5.8 14.13c-.25.69-1.44 1.32-1.98 1.37-.51.05-.99.24-3.34-.7-2.82-1.11-4.63-3.99-4.77-4.18-.14-.19-1.14-1.52-1.14-2.9s.72-2.06.98-2.34c.25-.28.55-.35.74-.35h.53c.17 0 .4-.06.62.48.25.6.84 2.07.91 2.22.08.15.12.33.02.52-.1.19-.15.31-.29.48l-.44.51c-.14.14-.29.3-.12.59.17.28.75 1.24 1.61 2 1.11.99 2.04 1.3 2.33 1.44.29.15.46.12.63-.07.17-.19.72-.84.92-1.13.19-.28.38-.24.65-.14.26.1 1.68.79 1.97.94.29.14.48.21.55.33.07.12.07.69-.18 1.38z"/></svg>
                    Chamar no WhatsApp
                </a>
                <div class="my-6 flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-stone-400" aria-hidden="true">
                    <span class="h-px flex-1 bg-stone-200"></span>ou deixe seu contato<span class="h-px flex-1 bg-stone-200"></span>
                </div>
            @else
                <p class="mt-1 text-sm text-stone-500">Deixe seu contato e a equipe da {{ $clinica->nome }} fala com você pelo WhatsApp.</p>
            @endif

            <form method="POST" action="{{ route('leads.formulario.enviar', $clinica->slug) }}" class="{{ $clinica->linkWhatsappContato() ? '' : 'mt-6 ' }}space-y-4">
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
                                <option value="{{ $p['id'] }}" @selected((string) old('procedimento_id') === (string) $p['id'])>{{ $p['nome'] }}</option>
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
