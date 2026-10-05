<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Relacionamento\ResponderPesquisaAction;
use App\Http\Requests\ResponderPesquisaRequest;
use App\Models\PesquisaSatisfacao;
use App\Models\Scopes\ClinicaScope;
use App\Models\Scopes\ProfissionalScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/** Página pública da pesquisa de satisfação (link enviado ao paciente). */
class AvaliacaoController extends Controller
{
    public function mostrar(string $token): View
    {
        $pesquisa = $this->pesquisa($token);

        return view('avaliacao', ['pesquisa' => $pesquisa, 'clinica' => $pesquisa->clinica]);
    }

    public function responder(string $token, ResponderPesquisaRequest $request, ResponderPesquisaAction $responder): RedirectResponse
    {
        $pesquisa = $this->pesquisa($token);

        try {
            $responder->execute($pesquisa, (int) $request->validated('nota'), $request->validated('comentario'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['nota' => $e->getMessage()]);
        }

        return redirect()->route('avaliacao.mostrar', $token);
    }

    private function pesquisa(string $token): PesquisaSatisfacao
    {
        abort_unless(strlen($token) === 48 && ctype_alnum($token), 404);

        // Sem login nem clínica ativa: o token (aleatório, 48 caracteres) identifica a pesquisa
        return PesquisaSatisfacao::withoutGlobalScopes([ClinicaScope::class, ProfissionalScope::class])
            ->with(['clinica:id,nome,logo_path', 'paciente' => fn ($q) => $q->withoutGlobalScopes()->select(['id', 'nome'])])
            ->where('token', $token)
            ->firstOrFail();
    }
}
