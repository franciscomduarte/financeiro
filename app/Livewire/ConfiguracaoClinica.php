<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AtualizarConfiguracaoClinicaAction;
use App\Models\Clinica;
use App\Services\AsaasService;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/** Configurações da clínica ativa (somente administradores — rota com middleware "admin"). */
class ConfiguracaoClinica extends Component
{
    use Concerns\MensagemDeErro;
    use WithFileUploads;

    #[Url(history: true)]
    public string $aba = 'dados';

    // ─── Dados ────────────────────────────────────────────────────
    public string $nome          = '';
    public string $razaoSocial   = '';
    public string $cnpj          = '';
    public string $telefone      = '';
    public string $emailContato  = '';
    public string $endereco      = '';
    public string $slogan        = '';
    /** @var mixed */
    public $logo = null;

    // ─── Integrações (segredos nunca voltam para a tela) ──────────
    public string $whatsappNumero     = '';
    public string $evolutionInstance  = '';
    public string $evolutionApiKey    = '';
    public string $asaasApiKey        = '';
    public bool   $asaasSandbox       = false;
    public string $asaasWebhookToken  = '';

    // ─── Financeiro ───────────────────────────────────────────────
    public string $aliquotaImposto = '6.00';

    // ─── Nota fiscal (NFS-e pela Focus NFe) ─────────────────────
    public string $nfseToken             = '';
    public bool   $nfseHomologacao       = true;
    public string $inscricaoMunicipal    = '';
    public string $codigoMunicipio       = '';
    public string $nfseItemListaServico  = '';
    public string $nfseCodigoTributario  = '';
    public string $nfseAliquotaIss       = '';
    public bool   $nfseOptanteSimples    = true;
    public string $nfseDiscriminacao     = '';
    public string $nfsePadrao            = 'municipal';
    public string $nfseCodigoNacional    = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $c = $this->clinica();

        $this->nome              = $c->nome;
        $this->razaoSocial       = (string) $c->razao_social;
        $this->cnpj              = (string) $c->cnpj;
        $this->telefone          = (string) $c->telefone;
        $this->emailContato      = (string) $c->email_contato;
        $this->endereco          = (string) $c->endereco;
        $this->slogan            = (string) $c->slogan;
        $this->whatsappNumero    = (string) $c->whatsapp_numero;
        $this->evolutionInstance = (string) $c->evolution_instance;
        $this->asaasSandbox      = (bool) $c->asaas_sandbox;
        $this->nfseHomologacao      = (bool) $c->nfse_homologacao;
        $this->inscricaoMunicipal   = (string) $c->inscricao_municipal;
        $this->codigoMunicipio      = (string) $c->codigo_municipio;
        $this->nfseItemListaServico = (string) $c->nfse_item_lista_servico;
        $this->nfseCodigoTributario = (string) $c->nfse_codigo_tributario;
        $this->nfseAliquotaIss      = $c->nfse_aliquota_iss !== null ? (string) $c->nfse_aliquota_iss : '';
        $this->nfseOptanteSimples   = (bool) $c->nfse_optante_simples;
        $this->nfseDiscriminacao    = (string) $c->nfse_discriminacao_padrao;
        $this->nfsePadrao           = ($c->nfse_padrao ?? \App\Enums\PadraoNfse::Municipal)->value;
        $this->nfseCodigoNacional   = (string) $c->nfse_codigo_tributacao_nacional;
        $this->aliquotaImposto   = number_format((float) $c->aliquota_imposto, 2, '.', '');
    }

    private function clinica(): Clinica
    {
        return app(ClinicaAtual::class)->get();
    }

    public function salvarDados(AtualizarConfiguracaoClinicaAction $action): void
    {
        $this->validate([
            'nome'         => 'required|string|max:150',
            'razaoSocial'  => 'nullable|string|max:200',
            'cnpj'         => ['nullable', 'string', 'max:20', 'regex:/^[\d.\/-]+$/'],
            'telefone'     => 'nullable|string|max:30',
            'emailContato' => 'nullable|email|max:150',
            'endereco'     => 'nullable|string|max:300',
            'slogan'       => 'nullable|string|max:150',
            // Sem SVG (pode conter script); "dimensions" exige uma imagem que realmente abre (recusa arquivo vazio/corrompido)
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:min_width=16,min_height=16,max_width=4000,max_height=4000',
        ], [
            'cnpj.regex' => 'Use apenas números, ponto, barra e hífen.',
            'logo.max'        => 'O logo pode ter no máximo 2 MB.',
            'logo.dimensions' => 'Envie uma imagem válida (entre 16 e 4000 pixels).',
        ]);

        $this->salvar($action, [
            'nome'          => $this->nome,
            'razao_social'  => $this->razaoSocial ?: null,
            'cnpj'          => $this->cnpj ?: null,
            'telefone'      => $this->telefone ?: null,
            'email_contato' => $this->emailContato ?: null,
            'endereco'      => $this->endereco ?: null,
            'slogan'        => $this->slogan ?: null,
        ], $this->logo);
        $this->logo = null;
    }

    public function salvarIntegracoes(AtualizarConfiguracaoClinicaAction $action): void
    {
        $this->validate([
            'whatsappNumero'    => ['nullable', 'string', 'max:30', 'regex:/^[\d\s()+-]+$/'],
            'evolutionInstance' => ['nullable', 'string', 'max:100', 'regex:/^[\w.-]+$/'],
            'evolutionApiKey'   => 'nullable|string|max:255',
            'asaasApiKey'       => 'nullable|string|max:255',
            'asaasSandbox'      => 'boolean',
            'asaasWebhookToken' => 'nullable|string|max:255',
        ], [
            'whatsappNumero.regex'    => 'Use apenas números, espaço, parênteses, + e hífen.',
            'evolutionInstance.regex' => 'Use apenas letras, números, ponto, hífen e sublinhado.',
        ]);

        $this->salvar($action, [
            'whatsapp_numero'     => $this->whatsappNumero ?: null,
            'evolution_instance'  => $this->evolutionInstance ?: null,
            'evolution_api_key'   => $this->evolutionApiKey,
            'asaas_api_key'       => $this->asaasApiKey,
            'asaas_sandbox'       => $this->asaasSandbox,
            'asaas_webhook_token' => $this->asaasWebhookToken,
        ]);

        // Segredos digitados não ficam no estado do componente
        $this->evolutionApiKey = $this->asaasApiKey = $this->asaasWebhookToken = '';
    }

    public function salvarFinanceiro(AtualizarConfiguracaoClinicaAction $action): void
    {
        $this->aliquotaImposto = str_replace(',', '.', $this->aliquotaImposto);
        $this->validate(['aliquotaImposto' => 'required|numeric|min:0|max:100'], [
            'aliquotaImposto.max' => 'A alíquota deve ficar entre 0 e 100%.',
        ]);

        $this->salvar($action, ['aliquota_imposto' => round((float) $this->aliquotaImposto, 2)]);
    }

    public function salvarNotaFiscal(AtualizarConfiguracaoClinicaAction $action): void
    {
        $this->validate([
            'nfseToken'            => 'nullable|string|max:255',
            'nfseHomologacao'      => 'boolean',
            'inscricaoMunicipal'   => 'nullable|string|max:30',
            'codigoMunicipio'      => ['nullable', 'regex:/^\d{7}$/'],
            'nfseItemListaServico' => 'nullable|string|max:10',
            'nfseCodigoTributario' => 'nullable|string|max:30',
            'nfseAliquotaIss'      => 'nullable|numeric|min:0|max:10',
            'nfseOptanteSimples'   => 'boolean',
            'nfseDiscriminacao'    => 'nullable|string|max:1000',
            'nfsePadrao'           => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\PadraoNfse::class)],
            'nfseCodigoNacional'   => ['nullable', 'regex:/^\d{6}$/'],
        ], [
            'nfseCodigoNacional.regex' => 'Use os 6 números do código de tributação nacional. Ex.: 060201.',
            'codigoMunicipio.regex' => 'Use o código IBGE de 7 números. Ex.: 5300108 (Brasília).',
            'nfseAliquotaIss.max'   => 'A alíquota do ISS vai de 0 a 10%.',
        ]);

        $this->salvar($action, [
            'nfse_token'                => $this->nfseToken,
            'nfse_homologacao'          => $this->nfseHomologacao,
            'inscricao_municipal'       => trim($this->inscricaoMunicipal) ?: null,
            'codigo_municipio'          => trim($this->codigoMunicipio) ?: null,
            'nfse_item_lista_servico'   => trim($this->nfseItemListaServico) ?: null,
            'nfse_codigo_tributario'    => trim($this->nfseCodigoTributario) ?: null,
            'nfse_aliquota_iss'         => $this->nfseAliquotaIss !== '' ? (float) str_replace(',', '.', $this->nfseAliquotaIss) : null,
            'nfse_optante_simples'      => $this->nfseOptanteSimples,
            'nfse_discriminacao_padrao' => trim($this->nfseDiscriminacao) ?: null,
            'nfse_padrao'               => $this->nfsePadrao,
            'nfse_codigo_tributacao_nacional' => preg_replace('/\D/', '', $this->nfseCodigoNacional) ?: null,
        ]);
        $this->nfseToken = '';
    }

    public function removerSegredo(string $campo, AtualizarConfiguracaoClinicaAction $action): void
    {
        try {
            $action->removerSegredo($this->clinica(), $campo);
            app(ClinicaAtual::class)->definir($this->clinica()->fresh());
            $this->flashSucesso = 'Chave removida.';
        } catch (Throwable $e) {
            $this->flashErro = 'Não foi possível remover a chave. Tente de novo em instantes.';
        }
    }

    public function testarWhatsApp(WhatsAppService $whatsapp): void
    {
        $numero = preg_replace('/\D/', '', $this->clinica()->whatsapp_numero ?? '');
        if ($numero === '' || ! $this->clinica()->whatsappConfigurado()) {
            $this->flashErro = 'Salve a instância, a chave e o número do WhatsApp antes de testar.';
            return;
        }

        if ($whatsapp->enviarTexto($numero, "✅ Teste do sistema: o WhatsApp da {$this->clinica()->nome} está conectado.")) {
            $this->flashSucesso = 'Mensagem de teste enviada para ' . $this->clinica()->whatsapp_numero . '.';
        } else {
            $this->flashErro = 'O WhatsApp não respondeu. Confira a instância e a chave.';
        }
    }

    public function testarAsaas(AsaasService $asaas): void
    {
        try {
            if ($asaas->testarConexao()) {
                $this->flashSucesso = 'Conexão com o Asaas funcionando.';
            } else {
                $this->flashErro = 'O Asaas recusou a chave. Confira a chave e o ambiente (sandbox/produção).';
            }
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e);
        }
    }

    private function salvar(AtualizarConfiguracaoClinicaAction $action, array $dados, $logo = null): void
    {
        try {
            $clinica = $action->execute($this->clinica(), $dados, $logo);
            app(ClinicaAtual::class)->definir($clinica);
            $this->flashSucesso = 'Alterações salvas.';
        } catch (Throwable $e) {
            Log::error('[ConfiguracaoClinica] erro ao salvar', ['message' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível salvar. Tente de novo em instantes.';
        }
    }

    public function render(): View
    {
        return view('livewire.configuracao-clinica', [
            'clinica'    => $this->clinica(),
            'webhookUrl' => route('webhook.asaas'),
        ])->layout('layouts.app', ['title' => 'Dados da clínica']);
    }
}
