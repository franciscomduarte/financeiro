<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Contracts\AssistenteIa;
use App\Enums\EtapaLead;
use App\Enums\TipoInteracaoLead;
use App\Jobs\AvisarEquipeLeadJob;
use App\Models\AssistenteConfiguracao;
use App\Models\AssistenteConhecimento;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Models\Procedimento;
use App\Services\Assistente\FerramentasAssistente;
use App\Services\Assistente\RespostaAssistente;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Assistente virtual: responde a última mensagem do lead no WhatsApp com base no treinamento da clínica
 * (rodar com a clínica ativa). Só fala com leads em aberto, não pausados e dentro do limite do mês.
 */
class ResponderLeadAction
{
    /** Mensagens da conversa enviadas ao modelo (as mais recentes). */
    private const HISTORICO = 30;

    public function __construct(
        private readonly AssistenteIa $ia,
        private readonly FerramentasAssistente $ferramentas,
        private readonly WhatsAppService $whatsapp,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    /** Chave que marca "o assistente está enviando": o eco no webhook não conta como resposta da equipe. */
    public static function chaveEnviando(string $leadId): string
    {
        return "assistente:enviando:{$leadId}";
    }

    /** @return string o que aconteceu: respondido | passou_para_equipe | desligado | pausado | limite | sem_whatsapp | nada_a_responder | recusou | falha_envio */
    public function execute(string $leadId): string
    {
        $config  = AssistenteConfiguracao::atual();
        $clinica = $this->clinicaAtual->get();
        $lead    = Lead::query()->find($leadId);

        $motivo = match (true) {
            ! $config->ativo                                                 => 'desligado',
            $lead === null || ! $lead->etapa->aberta()                        => 'nada_a_responder',
            $lead->assistente_pausado_em !== null                            => 'pausado',
            $clinica === null || ! $clinica->whatsappConfigurado()            => 'sem_whatsapp',
            $config->limiteAtingido()                                        => 'limite',
            default                                                           => null,
        };
        if ($motivo !== null) {
            if ($motivo === 'limite') {
                Log::warning('[Assistente] limite de respostas do mês atingido', ['tenant_id' => $clinica?->id, 'lead_id' => $leadId]);
            }

            return $motivo;
        }

        $mensagens = $this->conversa($lead);
        if ($mensagens === [] || end($mensagens)['role'] !== 'user') {
            return 'nada_a_responder';
        }

        $resposta = $this->ia->responder(
            $this->instrucoes($config), $this->contexto($lead), $mensagens, $this->ferramentas->definicoes($config),
            fn (string $nome, array $entrada): string => $this->ferramentas->executar($lead, $config, $nome, $entrada),
        );
        $config->contarResposta();
        $this->registrarUso($lead, $resposta);

        if ($resposta->recusou) {
            $this->pausar($lead, 'O assistente não pôde responder esta conversa.');
            AvisarEquipeLeadJob::dispatch($lead->id, "🙋 O assistente não pôde responder {$lead->nome}. Continue o atendimento pelo WhatsApp.");

            return 'recusou';
        }

        $lead->refresh();
        if ($resposta->texto === '') {
            return $lead->assistente_pausado_em ? 'passou_para_equipe' : 'nada_a_responder';
        }

        return $this->enviar($lead, $resposta->texto) ? ($lead->assistente_pausado_em ? 'passou_para_equipe' : 'respondido') : 'falha_envio';
    }

    /**
     * Teste do treinamento na tela de configuração: nada é enviado nem gravado.
     *
     * @param  list<array{role: string, content: string}>  $historico
     */
    public function simular(array $historico): RespostaAssistente
    {
        $config = AssistenteConfiguracao::atual();

        return $this->ia->responder(
            $this->instrucoes($config),
            $this->contextoBase() . "\nConversa de teste na tela de treinamento (pessoa fictícia).",
            $historico,
            $this->ferramentas->definicoes($config),
            fn (string $nome, array $entrada): string => $this->ferramentas->executar(null, $config, $nome, $entrada, simulacao: true),
        );
    }

    /** Parte fixa (vai para o cache): regras, treinamento da clínica e procedimentos. */
    public function instrucoes(AssistenteConfiguracao $config): string
    {
        $clinica = $this->clinicaAtual->get();
        $nome    = $config->nome ?: 'Assistente';

        $dados = array_filter([
            'Clínica: ' . ($clinica?->nome ?? config('app.name')),
            $clinica?->endereco ? "Endereço: {$clinica->endereco}" : null,
            $clinica?->telefone ? "Telefone: {$clinica->telefone}" : null,
            $clinica?->email_contato ? "E-mail: {$clinica->email_contato}" : null,
        ]);

        $procedimentos = Procedimento::query()->select(['id', 'nome', 'descricao', 'duracao_minutos', 'valor'])
            ->where('ativo', true)->orderBy('nome')->limit(150)->get()
            ->map(fn (Procedimento $p) => "- {$p->nome} ({$p->duracao_minutos} min"
                . ($config->informar_precos && (float) $p->valor > 0 ? ', R$ ' . number_format((float) $p->valor, 2, ',', '.') : '') . ')'
                . (filled($p->descricao) ? ': ' . trim((string) $p->descricao) : ''))
            ->implode("\n");

        $conhecimento = AssistenteConhecimento::query()->select(['titulo', 'conteudo'])->where('ativo', true)
            ->orderBy('titulo')->limit(200)->get()
            ->map(fn (AssistenteConhecimento $c) => "### {$c->titulo}\n" . trim($c->conteudo))
            ->implode("\n\n");

        $agenda = $config->podeAgendar()
            ? "Você pode agendar a avaliação ({$config->procedimentoAvaliacao?->nome}). Antes de oferecer horários, use consultar_horarios; ofereça 2 ou 3 opções. "
              . 'Quando a pessoa escolher, confirme o dia e a hora com ela e use agendar_avaliacao. Pergunte o nome completo se ainda não souber. Nunca diga que agendou sem a ferramenta confirmar.'
            : 'Você não agenda. Quando a pessoa quiser agendar, use passar_para_equipe.';

        return <<<TXT
        Você é {$nome}, assistente virtual da clínica no WhatsApp. Você conversa com pessoas interessadas (leads) que ainda não são pacientes.
        Seu objetivo: tirar dúvidas com as informações abaixo e levar a pessoa a agendar uma avaliação.

        Como responder:
        - Português do Brasil, tom acolhedor e profissional, mensagens curtas como no WhatsApp (até 3 frases curtas). Use no máximo um emoji quando combinar.
        - Use só as informações deste texto e o que as ferramentas devolverem. Se não souber, diga que vai verificar com a equipe e use passar_para_equipe. Nunca invente preço, prazo, promoção, resultado ou horário.
        - Não faça diagnóstico nem indique tratamento para um caso específico; diga que isso é avaliado na consulta.
        - Se perguntarem, diga que é a assistente virtual da clínica.
        - Use passar_para_equipe quando a pessoa pedir para falar com alguém, reclamar, falar de assunto de saúde/urgência, pedir desconto ou condição especial, ou mandar algo que você não entende (áudio, foto, documento).
        - Não peça dados sensíveis (documentos, dados de cartão). Para agendar basta o nome.
        - {$agenda}

        ## Dados da clínica
        {$this->linhas($dados)}

        ## Procedimentos
        {$procedimentos}

        ## Treinamento da clínica
        {$conhecimento}

        ## Instruções extras da clínica
        {$this->ou($config->instrucoes, 'Nenhuma.')}
        TXT;
    }

    private function linhas(array $itens): string
    {
        return implode("\n", $itens);
    }

    private function ou(?string $valor, string $padrao): string
    {
        return filled($valor) ? trim((string) $valor) : $padrao;
    }

    /** Parte que muda a cada resposta (fica fora do cache). */
    private function contextoBase(): string
    {
        $agora = now()->timezone((string) config('clinica.fuso_horario'))->locale('pt_BR');

        return 'Agora: ' . ucfirst($agora->translatedFormat('l, d/m/Y H:i')) . '.';
    }

    private function contexto(Lead $lead): string
    {
        $lead->loadMissing('procedimento:id,nome');

        return $this->contextoBase() . "\nContato: {$lead->nome}"
            . ($lead->procedimento || $lead->interesse ? '; interesse: ' . ($lead->procedimento?->nome ?? $lead->interesse) : '')
            . ($lead->etapa === EtapaLead::AvaliacaoAgendada ? '; já tem avaliação agendada.' : '.');
    }

    /** @return list<array{role: string, content: string}> conversa do WhatsApp, terminando na mensagem do lead */
    private function conversa(Lead $lead): array
    {
        $itens = LeadInteracao::query()->select(['id', 'tipo', 'texto', 'mensagem_id', 'created_at'])
            ->where('lead_id', $lead->id)
            ->whereIn('tipo', [TipoInteracaoLead::WhatsAppRecebido->value, TipoInteracaoLead::WhatsAppEnviado->value, TipoInteracaoLead::WhatsAppAssistente->value])
            ->latest('created_at')->orderByDesc('id')->limit(self::HISTORICO)->get()->reverse();

        $mensagens = [];
        foreach ($itens as $i) {
            $texto = trim((string) $i->texto);
            if ($texto === '' || ! $i->ehMensagemWhatsApp()) {
                continue;
            }
            $papel = $i->tipo === TipoInteracaoLead::WhatsAppRecebido ? 'user' : 'assistant';
            if ($mensagens === [] && $papel === 'assistant') {
                continue; // a conversa enviada ao modelo começa por uma mensagem do lead
            }
            $ultimo = array_key_last($mensagens);
            if ($ultimo !== null && $mensagens[$ultimo]['role'] === $papel) {
                $mensagens[$ultimo]['content'] .= "\n" . $texto; // mensagens seguidas viram uma só
            } else {
                $mensagens[] = ['role' => $papel, 'content' => $texto];
            }
        }

        return $mensagens;
    }

    private function enviar(Lead $lead, string $texto): bool
    {
        $texto    = mb_substr($texto, 0, 2000);
        $nacional = Telefone::nacional($lead->telefone);

        Cache::put(self::chaveEnviando($lead->id), true, now()->addMinute());
        $ok = $nacional !== null
            ? $this->whatsapp->enviarTextoParaTelefone($nacional, $texto)
            : (filled($lead->whatsapp_lid) && $this->whatsapp->enviarTexto($lead->whatsapp_lid . '@lid', $texto));
        $mensagemId = $this->whatsapp->ultimoIdMensagem;

        if (! $ok) {
            Log::warning('[Assistente] resposta não enviada', ['lead_id' => $lead->id, 'tenant_id' => $this->clinicaAtual->id()]);
            $this->pausar($lead, 'Não foi possível enviar a resposta pelo WhatsApp.');

            return false;
        }

        DB::transaction(function () use ($lead, $texto, $mensagemId): void {
            $dados = ['lead_id' => $lead->id, 'tipo' => TipoInteracaoLead::WhatsAppAssistente, 'texto' => $texto];
            // O eco do webhook pode ter chegado antes e gravado como "enviado": vira resposta do assistente
            $interacao = $mensagemId !== null
                ? LeadInteracao::createOrFirst(['mensagem_id' => $mensagemId], $dados)
                : LeadInteracao::create($dados + ['mensagem_id' => 'assistente-' . str()->uuid()]);
            if ($interacao->tipo !== TipoInteracaoLead::WhatsAppAssistente) {
                $interacao->update(['tipo' => TipoInteracaoLead::WhatsAppAssistente, 'user_id' => null]);
            }

            $atualizar = ['ultima_interacao_em' => now(), 'primeiro_contato_em' => $lead->primeiro_contato_em ?? now()];
            if ($lead->etapa === EtapaLead::Novo) {
                $atualizar['etapa'] = EtapaLead::EmContato;
            }
            $lead->update($atualizar);
        });

        return true;
    }

    private function pausar(Lead $lead, string $motivo): void
    {
        $lead->update(['assistente_pausado_em' => now(), 'assistente_motivo' => mb_substr($motivo, 0, 200), 'proximo_contato_em' => now()]);
    }

    private function registrarUso(Lead $lead, RespostaAssistente $resposta): void
    {
        Log::info('[Assistente] resposta gerada', [
            'tenant_id' => $this->clinicaAtual->id(), 'lead_id' => $lead->id,
            'tokens_entrada' => $resposta->tokensEntrada, 'tokens_saida' => $resposta->tokensSaida, 'recusou' => $resposta->recusou,
        ]);
    }
}
