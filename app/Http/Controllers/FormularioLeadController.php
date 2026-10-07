<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Leads\CriarLeadAction;
use App\Enums\OrigemLead;
use App\Enums\StatusClinica;
use App\Enums\TipoInteracaoLead;
use App\Http\Requests\FormularioLeadRequest;
use App\Models\Clinica;
use App\Models\Procedimento;
use App\Models\Scopes\ClinicaScope;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Formulário público "Quero ser atendida" de cada clínica (link para bio do Instagram, site...).
 * A origem vem no link: /c/{slug}/contato?origem=instagram. Quem envia vira lead.
 */
class FormularioLeadController extends Controller
{
    public function mostrar(string $slug, Request $request): View
    {
        $clinica = $this->clinica($slug);

        return view('leads.formulario', [
            'clinica'       => $clinica,
            'procedimentos' => Procedimento::query()->withoutGlobalScope(ClinicaScope::class)->where('tenant_id', $clinica->id)
                ->where('ativo', true)->orderBy('nome')->limit(100)->get(['id', 'nome']),
            'origem'        => $this->origem($request->query('origem'))->value,
            'enviado'       => (bool) session('lead_enviado'),
        ]);
    }

    public function enviar(string $slug, FormularioLeadRequest $request, CriarLeadAction $criar, ClinicaAtual $clinicaAtual): RedirectResponse
    {
        $clinica = $this->clinica($slug);
        $dados   = $request->validated();

        if (Telefone::chave($dados['telefone']) === null) {
            return back()->withInput()->withErrors(['telefone' => 'Informe seu WhatsApp com DDD. Ex.: (61) 99999-0000']);
        }

        try {
            $clinicaAtual->executarComo($clinica, fn () => $criar->execute([
                'nome'            => $dados['nome'],
                'telefone'        => $dados['telefone'],
                'email'           => $dados['email'] ?? null,
                'procedimento_id' => $dados['procedimento_id'] ?? null,
                'consentimento'   => true,
            ], $this->origem($dados['origem'] ?? null), TipoInteracaoLead::Formulario, $dados['mensagem'] ?? null, avisarEquipe: true));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['nome' => $e->getMessage()]);
        }

        return redirect()->route('leads.formulario', ['slug' => $slug])->with('lead_enviado', true);
    }

    private function clinica(string $slug): Clinica
    {
        return Clinica::query()->select(['id', 'nome', 'slug', 'status', 'logo_path', 'telefone', 'endereco'])
            ->where('slug', $slug)->where('status', '!=', StatusClinica::Bloqueada->value)->firstOrFail();
    }

    private function origem(mixed $valor): OrigemLead
    {
        $origem = OrigemLead::tryFrom(is_string($valor) ? mb_strtolower($valor) : '');

        return $origem !== null && in_array($origem, OrigemLead::doFormulario(), true) ? $origem : OrigemLead::Formulario;
    }
}
