<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Leads\CriarLeadAction;
use App\Actions\Leads\ResponderLeadAction;
use App\Contracts\AssistenteIa;
use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\StatusAgendamento;
use App\Enums\TipoInteracaoLead;
use App\Jobs\ResponderLeadJob;
use App\Livewire\AssistenteIndex;
use App\Livewire\LeadIndex;
use App\Models\Agendamento;
use App\Models\AssistenteConfiguracao;
use App\Models\AssistenteConhecimento;
use App\Models\GradeHorario;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use App\Services\Assistente\RespostaAssistente;
use App\Support\ClinicaAtual;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Assistente do WhatsApp: treinamento, resposta aos leads, agendamento, passagem para a equipe e travas. */
class AssistenteWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private Procedimento $avaliacao;

    private FakeAssistenteIa $ia;

    protected function setUp(): void
    {
        parent::setUp();
        config(['evolution.url' => 'https://evo.exemplo.com', 'services.anthropic.key' => 'teste']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc', 'whatsapp_numero' => '(61) 98888-0000', 'endereco' => 'Rua das Flores, 10']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['key' => ['id' => 'MSG-' . Str::random(6)]], 201)]);

        $this->ia = new FakeAssistenteIa;
        $this->app->instance(AssistenteIa::class, $this->ia);

        $this->avaliacao = Procedimento::create(['nome' => 'Avaliação', 'duracao_minutos' => 30, 'valor' => 0, 'ativo' => true]);
        Procedimento::create(['nome' => 'Botox', 'descricao' => 'Toxina botulínica', 'duracao_minutos' => 30, 'valor' => 1200, 'ativo' => true]);
        AssistenteConhecimento::create(['titulo' => 'Formas de pagamento', 'conteudo' => 'Pix, débito e até 10x no cartão.', 'ativo' => true]);
        AssistenteConfiguracao::atual()->update(['ativo' => true, 'nome' => 'Bia', 'procedimento_avaliacao_id' => $this->avaliacao->id]);
    }

    private function lead(string $texto = 'Oi, quanto custa o botox?'): Lead
    {
        [$lead] = app(CriarLeadAction::class)->execute(['nome' => 'Carla', 'telefone' => '61996666333'], OrigemLead::WhatsApp, TipoInteracaoLead::WhatsAppRecebido, $texto);

        return $lead;
    }

    private function profissionalComGrade(): Profissional
    {
        $p = new Profissional(['nome' => 'Ana', 'email' => 'ana@teste.com', 'ativo' => true]);
        $p->id = (string) Str::uuid();
        $p->save();
        foreach (range(0, 6) as $dia) {
            GradeHorario::create(['profissional_id' => $p->id, 'dia_semana' => $dia, 'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true]);
        }

        return $p;
    }

    public function test_responde_o_lead_com_o_treinamento_e_registra_na_conversa(): void
    {
        $lead = $this->lead();
        $this->ia->roteiro[] = fn () => new RespostaAssistente('Oi, Carla! O botox fica R$ 1.200,00. Quer agendar uma avaliação?', 900, 40);

        $this->assertSame('respondido', app(ResponderLeadAction::class)->execute($lead->id));

        [$instrucoes, $contexto, $mensagens] = $this->ia->chamadas[0];
        $this->assertStringContainsString('Você é Bia', $instrucoes);
        $this->assertStringContainsString('Pix, débito e até 10x no cartão.', $instrucoes);
        $this->assertStringContainsString('Botox (30 min, R$ 1.200,00): Toxina botulínica', $instrucoes);
        $this->assertStringContainsString('Rua das Flores, 10', $instrucoes);
        $this->assertStringContainsString('Contato: Carla', $contexto);
        $this->assertSame([['role' => 'user', 'content' => 'Oi, quanto custa o botox?']], $mensagens);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/message/sendText/lc') && $r['number'] === '5561996666333'
            && str_contains($r['textMessage']['text'] ?? '', 'R$ 1.200,00'));
        $lead->refresh();
        $this->assertSame(EtapaLead::EmContato, $lead->etapa);
        $this->assertNotNull($lead->primeiro_contato_em);
        $this->assertSame(1, LeadInteracao::query()->where('tipo', TipoInteracaoLead::WhatsAppAssistente)->count());
        $this->assertSame(1, AssistenteConfiguracao::atual()->respostasNoMes());

        // Sem preço quando a clínica desliga a opção
        AssistenteConfiguracao::atual()->update(['informar_precos' => false]);
        $this->assertStringNotContainsString('R$ 1.200,00', app(ResponderLeadAction::class)->instrucoes(AssistenteConfiguracao::atual()));
    }

    public function test_consulta_horarios_e_agenda_a_avaliacao_e_o_lead_continua_na_conversa(): void
    {
        $ana  = $this->profissionalComGrade();
        $lead = $this->lead('Quero agendar uma avaliação');
        $dia  = CarbonImmutable::today()->addDays(2);

        $this->ia->roteiro[] = function (array $ferramentas, Closure $executar) use ($dia): RespostaAssistente {
            $this->assertEqualsCanonicalizing(['passar_para_equipe', 'consultar_horarios', 'agendar_avaliacao'], array_column($ferramentas, 'name'));
            $horarios = $executar('consultar_horarios', ['a_partir_de' => $dia->toDateString()]);
            $this->assertStringContainsString($dia->toDateString() . '): 08:00, 08:30', $horarios);
            $ok = $executar('agendar_avaliacao', ['data' => $dia->toDateString(), 'hora' => '08:30', 'nome_completo' => 'Carla Souza']);
            $this->assertStringContainsString('Agendado com sucesso', $ok);
            // Segunda tentativa não cria outra avaliação
            $this->assertStringContainsString('Já existe uma avaliação agendada', $executar('agendar_avaliacao', ['data' => $dia->toDateString(), 'hora' => '09:00', 'nome_completo' => null]));

            return new RespostaAssistente('Prontinho! Sua avaliação ficou para ' . $dia->format('d/m') . ' às 08:30.');
        };

        $this->assertSame('respondido', app(ResponderLeadAction::class)->execute($lead->id));

        $agendamento = Agendamento::sole();
        $this->assertSame($ana->id, $agendamento->profissional_id);
        $this->assertSame($dia->toDateString() . ' 08:30', $agendamento->inicio_em->format('Y-m-d H:i'));
        $this->assertSame(StatusAgendamento::Agendado, $agendamento->status);
        $lead->refresh();
        $this->assertSame(EtapaLead::AvaliacaoAgendada, $lead->etapa);
        $this->assertSame('Carla Souza', $lead->paciente->nome);

        // Já é paciente, mas segue como lead em aberto: a nova mensagem entra na conversa (não é ignorada)
        app(ClinicaAtual::class)->definir(null);
        $this->postJson('/api/whatsapp/webhook', [
            'event' => 'messages.upsert', 'instance' => 'lc',
            'data'  => ['key' => ['remoteJid' => '556196666333@s.whatsapp.net', 'fromMe' => false, 'id' => 'NOVA1'], 'messageType' => 'conversation', 'message' => ['conversation' => 'Obrigada!']],
        ])->assertJson(['status' => 'lead_atualizado']);
    }

    public function test_passa_para_a_equipe_e_para_de_responder(): void
    {
        $lead = $this->lead('Quero falar com uma pessoa');
        $this->ia->roteiro[] = function (array $ferramentas, Closure $executar): RespostaAssistente {
            $executar('passar_para_equipe', ['motivo' => 'pediu atendimento humano']);

            return new RespostaAssistente('Claro! Já chamei alguém da equipe para continuar com você. 😊');
        };

        $this->assertSame('passou_para_equipe', app(ResponderLeadAction::class)->execute($lead->id));
        $lead->refresh();
        $this->assertNotNull($lead->assistente_pausado_em);
        $this->assertSame('pediu atendimento humano', $lead->assistente_motivo);
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'precisa de atendimento da equipe') && $r['number'] === '5561988880000');

        $this->assertSame('pausado', app(ResponderLeadAction::class)->execute($lead->id));
        $this->assertCount(1, $this->ia->chamadas);
    }

    public function test_so_responde_o_que_sabe_e_passa_a_pergunta_para_a_equipe(): void
    {
        $lead = $this->lead('Pode fazer botox grávida?');
        $instrucoes = app(ResponderLeadAction::class)->instrucoes(AssistenteConfiguracao::atual());
        $this->assertStringContainsString('Responda SOMENTE com o que está escrito', $instrucoes);
        $this->assertStringContainsString(AssistenteConfiguracao::RESPOSTA_SEM_INFORMACAO, $instrucoes);

        // Frase própria da clínica; mesmo sem texto do modelo, a pessoa recebe a frase
        AssistenteConfiguracao::atual()->update(['resposta_sem_informacao' => 'Vou ver com a Dra. e já te falo!']);
        $this->ia->roteiro[] = function (array $ferramentas, Closure $executar): RespostaAssistente {
            $executar('passar_para_equipe', ['motivo' => 'Sem resposta no treinamento: Pode fazer botox grávida?']);

            return new RespostaAssistente('');
        };

        $this->assertSame('passou_para_equipe', app(ResponderLeadAction::class)->execute($lead->id));
        $this->assertStringContainsString('Vou ver com a Dra. e já te falo!', $this->ia->chamadas[0][0]);
        Http::assertSent(fn ($r) => ($r['number'] ?? null) === '5561996666333' && ($r['textMessage']['text'] ?? null) === 'Vou ver com a Dra. e já te falo!');
        Http::assertSent(fn ($r) => ($r['number'] ?? null) === '5561988880000' && str_contains($r['textMessage']['text'] ?? '', 'Pode fazer botox grávida?'));
        $this->assertNotNull($lead->fresh()->assistente_pausado_em);
    }

    public function test_travas_desligado_limite_recusa_e_espera_por_mensagens_seguidas(): void
    {
        $lead = $this->lead();

        AssistenteConfiguracao::atual()->update(['limite_respostas_mes' => 0]);
        $this->assertSame('limite', app(ResponderLeadAction::class)->execute($lead->id));
        AssistenteConfiguracao::atual()->update(['ativo' => false, 'limite_respostas_mes' => 10]);
        $this->assertSame('desligado', app(ResponderLeadAction::class)->execute($lead->id));
        $this->assertCount(0, $this->ia->chamadas);

        // Recusa do modelo: pausa e avisa a equipe
        AssistenteConfiguracao::atual()->update(['ativo' => true]);
        $this->ia->roteiro[] = fn () => new RespostaAssistente('', recusou: true);
        $this->assertSame('recusou', app(ResponderLeadAction::class)->execute($lead->id));
        $this->assertNotNull($lead->fresh()->assistente_pausado_em);

        // Mensagens seguidas: só o job da última responde
        $lead->update(['assistente_pausado_em' => null]);
        $primeira = LeadInteracao::query()->where('lead_id', $lead->id)->where('tipo', TipoInteracaoLead::WhatsAppRecebido)->value('id');
        LeadInteracao::create(['lead_id' => $lead->id, 'tipo' => TipoInteracaoLead::WhatsAppRecebido, 'texto' => 'E o preenchimento?']);
        (new ResponderLeadJob($lead->id, $primeira))->handle(app(ResponderLeadAction::class));
        $this->assertCount(1, $this->ia->chamadas); // não chamou de novo
    }

    public function test_webhook_agenda_a_resposta_e_resposta_da_equipe_pausa_o_assistente(): void
    {
        Queue::fake();
        app(ClinicaAtual::class)->definir(null);
        $webhook = fn (string $id, string $texto, bool $daClinica = false) => $this->postJson('/api/whatsapp/webhook', [
            'event' => 'messages.upsert', 'instance' => 'lc',
            'data'  => ['key' => ['remoteJid' => '556196666333@s.whatsapp.net', 'fromMe' => $daClinica, 'id' => $id], 'pushName' => 'Carla', 'messageType' => 'conversation', 'message' => ['conversation' => $texto]],
        ]);

        $webhook('A1', 'Oi!')->assertJson(['status' => 'lead_criado']);
        Queue::assertPushed(ResponderLeadJob::class, 1);

        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $lead = Lead::sole();

        // Eco da mensagem do próprio assistente não pausa
        Cache::put(ResponderLeadAction::chaveEnviando($lead->id), true, 60);
        app(ClinicaAtual::class)->definir(null);
        $webhook('B1', 'Olá! Sou a Bia.', daClinica: true)->assertJson(['status' => 'resposta_registrada']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $this->assertNull($lead->fresh()->assistente_pausado_em);

        // Resposta da equipe pelo celular pausa
        Cache::forget(ResponderLeadAction::chaveEnviando($lead->id));
        app(ClinicaAtual::class)->definir(null);
        $webhook('C1', 'Oi Carla, aqui é a Paula!', daClinica: true);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $this->assertSame('A equipe respondeu pelo WhatsApp.', $lead->fresh()->assistente_motivo);

        // Retomar pela ficha
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));
        Livewire::test(LeadIndex::class)->call('abrir', $lead->id)
            ->assertSee('Assistente pausado')->call('retomarAssistente')->assertSee('Assistente respondendo');
        $this->assertNull($lead->fresh()->assistente_pausado_em);
    }

    public function test_tela_de_treinamento_configura_ensina_e_testa(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('leads.assistente'))->assertOk()->assertSee('Assistente do WhatsApp');

        $tela = Livewire::test(AssistenteIndex::class)
            ->set('procedimentoAvaliacaoId', '')->call('salvar')->assertHasErrors('procedimentoAvaliacaoId')
            ->set('procedimentoAvaliacaoId', (string) $this->avaliacao->id)->set('nome', 'Lia')->set('instrucoes', 'Trate por você.')->set('respostaSemInformacao', 'Já te respondo!')
            ->call('salvar')->assertHasNoErrors()->assertSee('Assistente ligado')
            ->call('novoConhecimento')->set('titulo', 'Estacionamento')->set('conteudo', 'Estacionamento gratuito no subsolo.')
            ->call('salvarConhecimento')->assertHasNoErrors()->assertSee('Estacionamento');
        $this->assertSame('Lia', AssistenteConfiguracao::atual()->nome);
        $this->assertSame('Já te respondo!', AssistenteConfiguracao::atual()->respostaSemInformacao());
        $this->assertSame(2, AssistenteConhecimento::count());

        $this->ia->roteiro[] = function (array $ferramentas, Closure $executar): RespostaAssistente {
            $this->assertStringContainsString('(Simulação)', $executar('passar_para_equipe', ['motivo' => 'teste']));

            return new RespostaAssistente('Temos estacionamento gratuito no subsolo!');
        };
        $tela->set('perguntaTeste', 'Tem estacionamento?')->call('testar')
            ->assertSee('Temos estacionamento gratuito no subsolo!')->assertSet('perguntaTeste', '');
        $this->assertStringContainsString('Estacionamento gratuito no subsolo.', $this->ia->chamadas[0][0]);
        $this->assertSame(0, Lead::count()); // simulação não grava nada
    }
}

/** IA falsa: cada chamada consome um passo do roteiro (recebe as ferramentas e o executor). */
class FakeAssistenteIa implements AssistenteIa
{
    /** @var list<Closure> */
    public array $roteiro = [];

    /** @var list<array{0: string, 1: string, 2: array}> */
    public array $chamadas = [];

    public function responder(string $instrucoes, string $contexto, array $mensagens, array $ferramentas, callable $executar): RespostaAssistente
    {
        $this->chamadas[] = [$instrucoes, $contexto, $mensagens];
        $passo = array_shift($this->roteiro) ?? fn () => new RespostaAssistente('ok');

        return $passo($ferramentas, Closure::fromCallable($executar));
    }
}
