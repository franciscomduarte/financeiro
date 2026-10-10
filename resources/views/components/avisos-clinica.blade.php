<div class="shrink-0">
    @if ($suporte)
        <div class="bg-indigo-600 px-4 py-2.5 md:px-6 text-sm text-white flex flex-wrap items-center justify-between gap-2" role="status">
            <p><strong>Modo suporte:</strong> você está vendo {{ $clinica->nome }} só para consulta. Nada pode ser alterado.</p>
            <form method="POST" action="{{ route('plataforma.suporte.sair') }}">
                @csrf
                <button type="submit" class="min-h-[40px] px-3 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">Sair do modo suporte</button>
            </form>
        </div>
    @elseif ($somenteLeitura)
        <div class="bg-red-50 border-b border-red-100 px-4 py-3 md:px-6 text-sm text-red-800 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" role="status">
            <p>
                <strong>Seu teste grátis terminou em {{ $clinica->teste_ate->format('d/m') }}.</strong>
                O sistema está em modo somente leitura: dá para consultar tudo, mas não para cadastrar ou alterar.
            </p>
            <div class="flex flex-wrap gap-2 shrink-0">
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-flex items-center min-h-[44px] px-4 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold">Quero assinar</a>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}" class="inline-flex items-center min-h-[44px] px-3 rounded-lg text-red-700 hover:bg-red-100 font-medium">{{ $email }}</a>
                @endif
            </div>
        </div>
    @elseif ($perfilConsulta)
        <div class="bg-sky-50 border-b border-sky-100 px-4 py-2.5 md:px-6 text-sm text-sky-800 dark:bg-sky-950/40 dark:border-sky-900 dark:text-sky-200" role="status">
            <p><strong>Somente consulta:</strong> você pode ver a agenda, os pacientes e os leads, mas não criar, alterar ou excluir.</p>
        </div>
    @elseif ($mostrarContagem)
        <div class="bg-rose-50 border-b border-rose-100 px-4 py-2.5 md:px-6 text-sm text-rose-800 flex flex-wrap items-center justify-between gap-2" role="status">
            <p>
                <strong>Teste grátis:</strong>
                {{ $diasRestantes === 1 ? 'hoje é o último dia' : "faltam {$diasRestantes} dias" }}
                <span class="text-rose-600">(até {{ $clinica->teste_ate->format('d/m') }})</span>
            </p>
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-flex items-center min-h-[44px] px-3 rounded-lg font-semibold text-rose-700 hover:bg-rose-100">Assinar agora →</a>
            @endif
        </div>
    @endif

    @if ($emailPendente)
        <div class="bg-amber-50 border-b border-amber-100 px-4 py-2.5 md:px-6 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-2" role="status">
            <p>Confirme seu e-mail <strong>{{ auth()->user()->email }}</strong> pelo link que enviamos.</p>
            <form method="POST" action="{{ route('verificacao.reenviar') }}">
                @csrf
                <button type="submit" class="min-h-[44px] px-3 rounded-lg font-semibold text-amber-800 hover:bg-amber-100">Reenviar link</button>
            </form>
        </div>
    @endif
</div>
