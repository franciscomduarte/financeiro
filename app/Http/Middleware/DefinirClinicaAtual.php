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

        $clinica = $this->resolver($request, $user);

        if ($clinica === null) {
            if ($request->routeIs(...self::ROTAS_LIVRES)) {
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
