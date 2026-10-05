<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Clinica;
use App\Models\User;
use App\Support\ClinicaAtual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a clínica ativa do usuário autenticado e a registra no Service Container (ClinicaAtual).
 * Ordem: header X-Clinica-Id (API) → sessão → única clínica do usuário. Com várias e nenhuma escolhida,
 * a web vai para a tela de escolha e a API responde 409.
 */
class DefinirClinicaAtual
{
    /** Rotas que funcionam sem clínica ativa (escolher/trocar/sair). */
    private const ROTAS_LIVRES = ['clinicas.escolher', 'clinicas.ativar', 'logout'];

    /** Rotas liberadas com o teste encerrado e e-mail ainda não confirmado. */
    private const ROTAS_CONFIRMACAO = ['verificacao.aviso', 'verificacao.reenviar', 'verificacao.confirmar'];

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        Context::add('user_id', $user->id);

        if ($suporte = $this->clinicaDeSuporte($request, $user)) {
            return $this->modoSuporte($request, $suporte, $next);
        }

        $clinica = $this->resolver($request, $user);

        if ($clinica === null) {
            if ($request->routeIs(...self::ROTAS_LIVRES) || $this->painelDaPlataforma($request, $user)) {
                return $next($request);
            }

            return $this->semClinica($request, $user);
        }

        if ($clinica->estaBloqueada() && ! $request->routeIs(...self::ROTAS_LIVRES)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Esta clínica está bloqueada.'], 403)
                : response()->view('clinicas.bloqueada', [
                    'clinica'           => $clinica,
                    'temOutrasClinicas' => $user->clinicas()->whereKeyNot($clinica->id)->exists(),
                ], 403);
        }

        $this->clinicaAtual->definir($clinica);
        if ($request->hasSession()) {
            $request->session()->put('clinica_id', $clinica->id);
        }
        $this->registrarAcesso($clinica);

        if ($clinica->somenteLeitura()) {
            return $this->testeEncerrado($request, $user, $next);
        }

        return $next($request);
    }

    /**
     * Teste grátis encerrado: só leitura. Quem não confirmou o e-mail precisa confirmar antes de
     * continuar vendo os dados. Gravações via HTTP (API, formulários) são recusadas aqui; as feitas
     * pelo Livewire são barradas nos Models (BelongsToClinica).
     */
    private function testeEncerrado(Request $request, User $user, Closure $next): Response
    {
        $livre = $request->routeIs(...self::ROTAS_LIVRES, ...self::ROTAS_CONFIRMACAO);

        if (! $user->hasVerifiedEmail() && ! $livre) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Confirme seu e-mail para continuar acessando.'], 403)
                : redirect()->route('verificacao.aviso');
        }

        $leitura = $request->isMethodSafe() || \Livewire\Livewire::isLivewireRequest() || $livre;
        if (! $leitura) {
            throw new \App\Exceptions\ClinicaSomenteLeituraException();
        }

        return $next($request);
    }

    /** Super admin no painel da plataforma (inclusive as requisições Livewire dele) não precisa de clínica. */
    private function painelDaPlataforma(Request $request, User $user): bool
    {
        return $user->is_super_admin && $request->routeIs('plataforma.*', 'livewire.*');
    }

    /** Clínica que o dono da plataforma está vendo como suporte (sessão), se houver. */
    private function clinicaDeSuporte(Request $request, User $user): ?Clinica
    {
        if (! $user->is_super_admin || ! $request->hasSession()) {
            return null;
        }

        $id = $request->session()->get(\App\Http\Controllers\SuporteClinicaController::SESSAO);

        return $id ? Clinica::find($id) : null;
    }

    /**
     * Suporte: vê a clínica (mesmo bloqueada ou com teste encerrado) sempre em somente leitura.
     * Gravações via HTTP são recusadas aqui; as do Livewire, nos Models. O painel da plataforma segue livre.
     */
    private function modoSuporte(Request $request, Clinica $clinica, Closure $next): Response
    {
        $this->clinicaAtual->definirSuporte($clinica);

        $leitura = $request->isMethodSafe()
            || \Livewire\Livewire::isLivewireRequest()
            || $request->routeIs('plataforma.*', 'logout');

        if (! $leitura) {
            throw new \App\Exceptions\ClinicaSomenteLeituraException(\App\Exceptions\ClinicaSomenteLeituraException::SUPORTE);
        }

        return $next($request);
    }

    /** Último acesso da clínica (painel da plataforma), gravado no máximo a cada 10 minutos. */
    private function registrarAcesso(Clinica $clinica): void
    {
        if ($clinica->ultimo_acesso_em === null || $clinica->ultimo_acesso_em->lt(now()->subMinutes(10))) {
            $clinica->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();
        }
    }

    private function resolver(Request $request, User $user): ?Clinica
    {
        $solicitada = $request->header('X-Clinica-Id')
            ?? ($request->hasSession() ? $request->session()->get('clinica_id') : null);

        if ($solicitada) {
            $clinica = $user->clinicas()->whereKey($solicitada)->first();
            if ($clinica) {
                return $clinica;
            }
            if ($request->hasSession()) {
                $request->session()->forget('clinica_id');
            }
        }

        $clinicas = $user->clinicas()->limit(2)->get();

        return $clinicas->count() === 1 ? $clinicas->first() : null;
    }

    private function semClinica(Request $request, User $user): Response
    {
        if ($user->is_super_admin && ! $request->expectsJson()) {
            return redirect()->route('plataforma.index');
        }

        $temClinicas = $user->clinicas()->exists();

        if ($request->expectsJson()) {
            return $temClinicas
                ? response()->json(['message' => 'Informe a clínica no header X-Clinica-Id.'], 409)
                : response()->json(['message' => 'Usuário sem clínica vinculada.'], 403);
        }

        return $temClinicas
            ? redirect()->route('clinicas.escolher')
            : response()->view('clinicas.sem-clinica', [], 403);
    }
}
