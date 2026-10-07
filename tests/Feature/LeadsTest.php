<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Leads\CriarLeadAction;
use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\TipoInteracaoLead;
use App\Livewire\AgendamentoIndex;
use App\Livewire\LeadIndex;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\User;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Gestão de leads: funil, formulário público, entrada pelo WhatsApp e conversão em paciente. */
class LeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc', 'whatsapp_numero' => '(61) 98888-0000']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['key' => ['id' => 'MSG1']], 201)]);
    }

    public function test_chave_de_telefone_casa_com_e_sem_nono_digito(): void
    {
        $this->assertSame(Telefone::chave('(61) 99999-1111'), Telefone::chave('556199991111'));
        $this->assertSame('(61) 99999-1111', Telefone::formatar('556199991111'));
        $this->assertNull(Telefone::chave('12345'));
    }

    public function test_formulario_publico_cria_lead_com_origem_e_avisa_a_equipe(): void
    {
        $botox = Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 30, 'valor' => 900, 'ativo' => true]);
        app(ClinicaAtual::class)->definir(null);

        $this->get('/c/lc-estetica/contato?origem=instagram')->assertOk()->assertSee('Quer agendar uma avaliação?')->assertSee('Botox');
        $this->post('/c/lc-estetica/contato', ['nome' => 'Ana', 'telefone' => '61999991111', 'origem' => 'instagram'])
            ->assertSessionHasErrors('consentimento');
        $this->post('/c/lc-estetica/contato', ['nome' => 'Robô', 'telefone' => '61999991111', 'consentimento' => '1', 'site' => 'spam'])
            ->assertSessionHasErrors('site');

        $this->post('/c/lc-estetica/contato', [
            'nome' => 'Ana Lima', 'telefone' => '(61) 99999-1111', 'email' => 'ANA@x.com', 'procedimento_id' => $botox->id,
            'mensagem' => 'Quero saber o valor', 'consentimento' => '1', 'origem' => 'instagram',
        ])->assertRedirect('/c/lc-estetica/contato');
        $this->get('/c/lc-estetica/contato')->assertSee('Recebemos seu contato!');

        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $lead = Lead::sole();
        $this->assertSame(OrigemLead::Instagram, $lead->origem);
        $this->assertSame(EtapaLead::Novo, $lead->etapa);
        $this->assertSame('ana@x.com', $lead->email);
        $this->assertNotNull($lead->consentimento_em);
        $this->assertSame('Quero saber o valor', $lead->interacoes()->sole()->texto);
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'Novo lead — Instagram') && str_contains($r['textMessage']['text'], 'Ana Lima'));

        // Mesmo telefone de novo: não duplica, vira histórico
        app(ClinicaAtual::class)->definir(null);
        $this->post('/c/lc-estetica/contato', ['nome' => 'Ana', 'telefone' => '556199991111', 'consentimento' => '1'])->assertRedirect();
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $this->assertSame(1, Lead::count());
        $this->assertSame(2, LeadInteracao::count());

        $this->get('/c/nao-existe/contato')->assertNotFound();
    }

    public function test_mensagem_de_whatsapp_de_numero_novo_vira_lead_e_paciente_e_ignorado(): void
    {
        Paciente::create(['nome' => 'Paciente Antiga', 'telefone' => '(61) 97777-2222']);
        app(ClinicaAtual::class)->definir(null);

        $msg = fn (string $jid, string $texto, string $nome = 'Bia') => $this->postJson('/api/whatsapp/webhook', [
            'event' => 'messages.upsert', 'instance' => 'lc',
            'data'  => ['key' => ['remoteJid' => $jid, 'fromMe' => false, 'id' => 'X'], 'pushName' => $nome, 'messageType' => 'conversation', 'message' => ['conversation' => $texto]],
        ]);

        $msg('556196666333@s.whatsapp.net', 'Oi, quanto custa o preenchimento?')->assertJson(['status' => 'lead_criado']);
        $msg('556196666333@s.whatsapp.net', 'Alô?')->assertJson(['status' => 'lead_atualizado']);
        $msg('556177772222@s.whatsapp.net', 'Bom dia')->assertJson(['status' => 'paciente']);
        $msg('120363000000@g.us', 'grupo')->assertJson(['status' => 'ignored_own_message']);

        // "Webhook by Events" ligado: o evento vem no endereço; token no corpo também vale
        config(['services.whatsapp.webhook_token' => 'segredo']);
        $this->postJson('/api/whatsapp/webhook/messages-upsert', [
            'instance' => 'lc', 'apikey' => 'segredo',
            'data' => ['key' => ['remoteJid' => '556195555111@s.whatsapp.net', 'fromMe' => false, 'id' => 'Y'], 'pushName' => 'Lia', 'messageType' => 'conversation', 'message' => ['conversation' => 'Oi']],
        ])->assertJson(['status' => 'lead_criado']);
        $this->postJson('/api/whatsapp/webhook/messages-upsert', ['instance' => 'lc', 'apikey' => 'errado', 'data' => []])->assertJson(['status' => 'unauthorized']);
        $this->postJson('/api/whatsapp/webhook/messages-upsert', ['instance' => 'outra', 'apikey' => 'segredo',
            'data' => ['key' => ['remoteJid' => '556195555222@s.whatsapp.net', 'fromMe' => false, 'id' => 'Z'], 'message' => ['conversation' => 'Oi']],
        ])->assertJson(['status' => 'ignored_unknown_instance']);
        config(['services.whatsapp.webhook_token' => null]);

        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $this->assertSame(2, Lead::query()->count());
        $lead = Lead::query()->where('nome', 'Bia')->sole();
        $this->assertSame('Bia', $lead->nome);
        $this->assertSame('(61) 99666-6333', $lead->telefone);
        $this->assertSame(OrigemLead::WhatsApp, $lead->origem);
        $this->assertSame(1, $lead->interacoes()->count()); // mensagens seguidas não repetem
    }

    public function test_funil_contato_perda_e_conversao_em_paciente(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));
        [$lead] = app(CriarLeadAction::class)->execute(['nome' => 'Carla Dias', 'telefone' => '61955554444'], OrigemLead::Indicacao);

        Livewire::test(LeadIndex::class)
            ->assertSee('Carla Dias')
            ->call('abrir', $lead->id)
            ->set('contatoTipo', 'ligacao')->set('contatoTexto', 'Vai pensar')->set('contatoProximo', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('registrarContato')
            ->assertSet('flashSucesso', 'Contato registrado.');

        $lead->refresh();
        $this->assertSame(EtapaLead::EmContato, $lead->etapa); // o primeiro contato tira de "Novo"
        $this->assertNotNull($lead->primeiro_contato_em);
        $this->assertNotNull($lead->proximo_contato_em);

        Livewire::test(LeadIndex::class)
            ->call('mover', $lead->id, 'avaliacao_agendada')
            ->call('mover', $lead->id, 'fechado')->assertSet('flashErro', 'Para fechar, abra o lead e use "Converter em paciente".')
            ->call('mover', $lead->id, 'perdido')->assertSet('perdendoId', $lead->id)
            ->call('confirmarPerda')->assertHasErrors('motivoPerda')
            ->set('motivoPerda', 'Achou caro')->call('confirmarPerda')->assertSet('flashSucesso', 'Lead marcado como perdido.');
        $this->assertSame('Achou caro', $lead->fresh()->motivo_perda);

        Livewire::test(LeadIndex::class)->call('mover', $lead->id, 'em_contato');
        Livewire::test(LeadIndex::class, ['leadId' => $lead->id])
            ->set('leadId', $lead->id)
            ->call('converter')
            ->assertRedirect(route('agenda.index', ['paciente' => Paciente::sole()->id]));

        $paciente = Paciente::sole();
        $this->assertSame('Carla Dias', $paciente->nome);
        $this->assertSame('Indicação', $paciente->origem);
        $this->assertSame(EtapaLead::Fechado, $lead->fresh()->etapa);
        $this->assertSame($paciente->id, $lead->fresh()->paciente_id);
        $this->assertTrue($lead->interacoes()->where('tipo', TipoInteracaoLead::Convertido)->exists());

        // A agenda abre o agendamento com o paciente escolhido
        Livewire::withQueryParams(['paciente' => $paciente->id])->test(AgendamentoIndex::class)
            ->assertSet('criarPacienteId', $paciente->id)->assertSet('modalCriar', true);
    }

    public function test_cadastro_manual_avisa_se_ja_e_paciente_e_relatorio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        Paciente::create(['nome' => 'Dora', 'telefone' => '(61) 93333-1111']);

        Livewire::test(LeadIndex::class)
            ->call('novo')
            ->set('nome', 'Dora')->set('telefone', '61 93333-1111')
            ->assertSee('Este contato já é do paciente')
            ->set('telefone', '123')->call('salvar')->assertHasErrors('telefone')
            ->set('telefone', '61922221111')->set('origem', 'google')->call('salvar')
            ->assertSet('flashSucesso', 'Lead cadastrado.');

        [$outro] = app(CriarLeadAction::class)->execute(['nome' => 'Eva', 'telefone' => '61911112222'], OrigemLead::Google);
        app(\App\Actions\Leads\AtualizarLeadAction::class)->converter($outro->id);

        Livewire::test(LeadIndex::class)->set('aba', 'relatorio')
            ->assertSee('Taxa de conversão')->assertSee('50,0%')->assertSee('Google');

        $this->actingAs(User::factory()->create(['role' => 'financeiro']))->get('/leads')->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'recepcao']))->get('/leads')->assertOk()->assertDontSee('Relatório');
    }
}
