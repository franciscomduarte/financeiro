<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Notificacoes\SalvarConfiguracaoNotificacaoAction;
use App\Enums\CanalNotificacao;
use App\Enums\StatusNotificacao;
use App\Enums\TipoNotificacao;
use App\Jobs\DespacharNotificacoesJob;
use App\Livewire\NotificacaoIndex;
use App\Mail\NotificacaoMail;
use App\Models\Agendamento;
use App\Models\Notificacao;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AgendamentoService;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Central de notificações: avisos da agenda, lembretes agendados, retorno de entrega e tela. */
class NotificacoesTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $maria;
    private bool $whatsappRecusa = false;
    private Profissional $ana;
    private Procedimento $limpeza;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 5)->setTime(9, 0));
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        config(['evolution.url' => 'https://evo.exemplo.com', 'services.brevo.webhook_token' => 'segredo-brevo']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());

        Http::fake(fn () => $this->whatsappRecusa
            ? Http::response(['error' => 'not a whatsapp number'], 400)
            : Http::response(['key' => ['id' => 'MSG-' . Str::random(6)], 'status' => 'PENDING'], 201));
        Mail::fake();

        $this->maria = Paciente::create(['nome' => 'Maria Silva', 'telefone' => '(61) 99999-1111', 'email' => 'maria@exemplo.com'])->fresh();
        $this->ana   = new Profissional(['nome' => 'Ana Souza', 'email' => 'ana@x.com', 'ativo' => true]);
        $this->ana->id = (string) Str::uuid();
        $this->ana->save();
        $this->limpeza = Procedimento::create(['nome' => 'Limpeza de pele', 'duracao_minutos' => 60, 'valor' => 200, 'ativo' => true]);
    }

    private function marcar(string $quando = '2026-10-08 14:00'): Agendamento
    {
        return app(AgendamentoService::class)->criar([
            'paciente_id' => $this->maria->id, 'profissional_id' => $this->ana->id,
            'procedimento_id' => $this->limpeza->id, 'inicio_em' => $quando,
        ]);
    }

    public function test_marcar_envia_confirmacao_e_agenda_os_lembretes(): void
    {
        $a = $this->marcar();

        $confirmacoes = Notificacao::query()->where('tipo', TipoNotificacao::AgendamentoConfirmado)->get();
        $this->assertCount(2, $confirmacoes);
        $this->assertTrue($confirmacoes->every(fn ($n) => $n->status === StatusNotificacao::Enviada));

        $whats = $confirmacoes->firstWhere('canal', CanalNotificacao::WhatsApp);
        $this->assertStringStartsWith('MSG-', $whats->id_externo);
        $this->assertStringContainsString('Olá, *Maria*!', $whats->conteudo);
        $this->assertStringContainsString('*Limpeza de pele*', $whats->conteudo);
        $this->assertStringContainsString('08/10/2026', $whats->conteudo);
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'Agendamento confirmado'));
        Mail::assertSent(NotificacaoMail::class, fn (NotificacaoMail $m) => $m->hasTo('maria@exemplo.com')
            && str_contains($m->assunto, 'Agendamento confirmado: 08/10/2026 às 14:00'));

        $lembretes = Notificacao::query()->where('status', StatusNotificacao::Agendada)->orderBy('agendada_para')->get();
        $this->assertCount(4, $lembretes); // véspera e no dia × WhatsApp e e-mail
        $this->assertSame('2026-10-07 14:00', $lembretes[0]->agendada_para->timezone('America/Sao_Paulo')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-08 12:00', $lembretes[3]->agendada_para->timezone('America/Sao_Paulo')->format('Y-m-d H:i'));
        $this->assertSame((string) $a->id, $lembretes[0]->origem_id);
    }

    public function test_lembrete_sai_na_hora_com_o_texto_do_momento(): void
    {
        $this->marcar();

        $this->travelTo(now()->setTimezone('America/Sao_Paulo')->setDate(2026, 10, 7)->setTime(13, 59));
        (new DespacharNotificacoesJob())->handle();
        $this->assertSame(4, Notificacao::query()->where('status', StatusNotificacao::Agendada)->count());

        $this->travel(2)->minutes();
        (new DespacharNotificacoesJob())->handle();

        $vespera = Notificacao::query()->where('tipo', TipoNotificacao::LembreteVespera)->where('canal', CanalNotificacao::WhatsApp)->sole();
        $this->assertSame(StatusNotificacao::Enviada, $vespera->status);
        $this->assertStringContainsString('Seu horário é *amanhã*', $vespera->conteudo);
        $this->assertSame(2, Notificacao::query()->where('status', StatusNotificacao::Agendada)->count());
    }

    public function test_remarcar_e_cancelar_refazem_os_lembretes(): void
    {
        $a    = $this->marcar();
        $novo = app(AgendamentoService::class)->reagendar($a, '2026-10-10', '09:00');

        $this->assertSame(4, Notificacao::query()->where('origem_id', (string) $a->id)->where('status', StatusNotificacao::Cancelada)->count());
        $this->assertSame(4, Notificacao::query()->where('origem_id', (string) $novo->id)->where('status', StatusNotificacao::Agendada)->count());
        $this->assertSame(2, Notificacao::query()->where('tipo', TipoNotificacao::AgendamentoRemarcado)->where('status', StatusNotificacao::Enviada)->count());

        app(AgendamentoService::class)->cancelar($novo->fresh(), 'Paciente viajou');

        $this->assertSame(0, Notificacao::query()->where('status', StatusNotificacao::Agendada)->count());
        $cancelamento = Notificacao::query()->where('tipo', TipoNotificacao::AgendamentoCancelado)->where('canal', CanalNotificacao::WhatsApp)->sole();
        $this->assertStringContainsString('📝 Motivo: Paciente viajou', $cancelamento->conteudo);
    }

    public function test_lembrete_de_horario_que_mudou_por_fora_nao_sai(): void
    {
        $a = $this->marcar();
        Agendamento::query()->whereKey($a->id)->update(['status' => 'cancelado']); // sem passar pelo model (sem observer)

        $this->travelTo(now()->addDays(4));
        (new DespacharNotificacoesJob())->handle();

        $this->assertSame(0, Notificacao::query()->whereIn('tipo', [TipoNotificacao::LembreteVespera, TipoNotificacao::LembreteDia])->where('status', StatusNotificacao::Enviada)->count());
        $this->assertSame(4, Notificacao::query()->where('erro', 'O agendamento mudou ou o horário já passou.')->count());
    }

    public function test_falha_do_whatsapp_fica_registrada_e_pode_reenviar(): void
    {
        $this->whatsappRecusa = true;
        $this->marcar();

        $falhou = Notificacao::query()->where('tipo', TipoNotificacao::AgendamentoConfirmado)->where('canal', CanalNotificacao::WhatsApp)->sole();
        $this->assertSame(StatusNotificacao::Falhou, $falhou->status);
        $this->assertStringContainsString('não aceitou a mensagem', $falhou->erro);

        $this->whatsappRecusa = false;
        Livewire::test(NotificacaoIndex::class)
            ->call('acao', 'reenviar', $falhou->id)
            ->assertSet('flashSucesso', '1 notificação reenviada.');

        $nova = Notificacao::query()->where('canal', CanalNotificacao::WhatsApp)->where('tipo', TipoNotificacao::AgendamentoConfirmado)->whereKeyNot($falhou->id)->sole();
        $this->assertSame(StatusNotificacao::Enviada, $nova->status);
        $this->assertStringStartsWith('MSG-', $nova->id_externo);
        $this->assertSame(StatusNotificacao::Falhou, $falhou->fresh()->status); // o original fica no histórico
    }

    public function test_configuracao_desliga_canal_muda_antecedencia_e_texto(): void
    {
        $this->marcar();

        app(SalvarConfiguracaoNotificacaoAction::class)->execute(TipoNotificacao::LembreteVespera, [
            'whatsapp' => true, 'email' => false, 'antecedencia_minutos' => 2880,
            'assunto' => 'Lembrete', 'texto' => 'Oi {primeiro_nome}, até {quando} às {hora}!',
        ]);

        $vespera = Notificacao::query()->where('tipo', TipoNotificacao::LembreteVespera)->where('status', StatusNotificacao::Agendada)->get();
        $this->assertCount(1, $vespera);
        $this->assertSame(CanalNotificacao::WhatsApp, $vespera[0]->canal);
        $this->assertSame('2026-10-06 14:00', $vespera[0]->agendada_para->timezone('America/Sao_Paulo')->format('Y-m-d H:i'));

        $this->travelTo(now()->setTimezone('America/Sao_Paulo')->setDate(2026, 10, 6)->setTime(14, 1));
        (new DespacharNotificacoesJob())->handle();
        $this->assertSame('Oi Maria, até quinta-feira, 08/10 às 14:00!', $vespera[0]->fresh()->conteudo);
    }

    public function test_retorno_de_entrega_do_whatsapp_e_da_brevo(): void
    {
        $this->marcar();
        $whats = Notificacao::query()->where('canal', CanalNotificacao::WhatsApp)->where('status', StatusNotificacao::Enviada)->sole();
        $email = Notificacao::query()->where('canal', CanalNotificacao::Email)->where('status', StatusNotificacao::Enviada)->sole();

        app(ClinicaAtual::class)->definir(null); // webhooks chegam sem clínica
        $this->postJson('/api/whatsapp/webhook', ['event' => 'messages.update', 'instance' => 'lc', 'data' => ['keyId' => $whats->id_externo, 'status' => 'DELIVERY_ACK']])->assertOk();
        $this->postJson('/api/whatsapp/webhook', ['event' => 'MESSAGES_UPDATE', 'data' => [['key' => ['id' => $whats->id_externo], 'update' => ['status' => 4]]]])->assertOk();
        $this->postJson('/api/whatsapp/webhook', ['event' => 'messages.update', 'data' => ['keyId' => $whats->id_externo, 'status' => 'DELIVERY_ACK']])->assertOk(); // atrasado: não volta

        $this->postJson('/api/webhooks/brevo/errado', ['event' => 'delivered', 'X-Mailin-custom' => 'notificacao:' . $email->id])->assertUnauthorized();
        $this->postJson('/api/webhooks/brevo/segredo-brevo', ['event' => 'delivered', 'X-Mailin-custom' => 'notificacao:' . $email->id])->assertOk();

        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $this->assertSame(StatusNotificacao::Lida, $whats->fresh()->status);
        $this->assertNotNull($whats->fresh()->entregue_em);
        $this->assertSame(StatusNotificacao::Entregue, $email->fresh()->status);
    }

    public function test_tela_lista_filtra_e_age_em_lote(): void
    {
        $this->marcar();
        Paciente::create(['nome' => 'João Lima', 'telefone' => '(61) 98888-2222'])->fresh();

        Livewire::test(NotificacaoIndex::class)
            ->assertSee('Maria Silva')->assertSee('Agendamento confirmado')
            ->set('busca', 'joão')->assertSee('Nada encontrado')
            ->set('busca', '')->set('filtroCanal', 'email')->assertDontSee('WhatsApp ligado')
            ->call('trocarAba', 'agendadas')->assertSee('Lembrete: seu horário é')
            ->tap(function ($tela): void {
                $ids = Notificacao::query()->where('status', StatusNotificacao::Agendada)->where('tipo', TipoNotificacao::LembreteDia)->pluck('id')->all();
                $tela->set('selecionados', $ids)->call('emLote', 'cancelar')->assertSet('flashSucesso', '2 notificações canceladas.');
            })
            ->tap(function ($tela): void {
                $id = Notificacao::query()->where('status', StatusNotificacao::Agendada)->where('canal', CanalNotificacao::WhatsApp)->value('id');
                $tela->call('acao', 'enviarAgora', $id)->assertSet('flashSucesso', '1 notificação enviada agora.');
            });

        $this->assertSame(1, Notificacao::query()->where('status', StatusNotificacao::Agendada)->count());
        $this->assertSame(1, Notificacao::query()->where('tipo', TipoNotificacao::LembreteVespera)->where('status', StatusNotificacao::Enviada)->count());
    }

    public function test_acesso_por_perfil(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'recepcao']))->get('/notificacoes')->assertOk()->assertDontSee('Configurar avisos');
        $this->actingAs(User::factory()->create(['role' => 'financeiro']))->get('/notificacoes')->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'profissional']))->get('/notificacoes')->assertRedirect();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(NotificacaoIndex::class)
            ->call('trocarAba', 'configurar')->assertSee('Lembrete (véspera)')
            ->call('editar', 'agendamento_confirmado')->assertSet('cfgTexto', TipoNotificacao::AgendamentoConfirmado->textoPadrao())
            ->set('cfgTexto', '')->call('salvarConfiguracao')->assertHasErrors('cfgTexto')
            ->set('cfgTexto', 'Confirmado, {primeiro_nome}!')->set('cfgEmail', false)->call('salvarConfiguracao')
            ->assertHasNoErrors()->assertSee('Texto personalizado');

        $this->marcar();
        $this->assertSame(0, Notificacao::query()->where('tipo', TipoNotificacao::AgendamentoConfirmado)->where('canal', CanalNotificacao::Email)->count());
        $this->assertSame('Confirmado, Maria!', Notificacao::query()->where('tipo', TipoNotificacao::AgendamentoConfirmado)->sole()->conteudo);
    }

    public function test_envios_de_outras_telas_entram_no_historico(): void
    {
        $orcamento = \App\Models\Orcamento::create(['paciente_id' => $this->maria->id, 'numero' => 1, 'status' => 'aberto', 'validade' => now()->addDays(15)->toDateString(), 'subtotal' => 100, 'desconto' => 0, 'total' => 100]);
        app(\App\Actions\Orcamentos\EnviarOrcamentoWhatsAppAction::class)->execute($orcamento->id);

        $n = Notificacao::query()->where('tipo', TipoNotificacao::Orcamento)->sole();
        $this->assertSame(StatusNotificacao::Enviada, $n->status);
        $this->assertSame((string) $orcamento->id, $n->origem_id);
        $this->assertStringStartsWith('MSG-', $n->id_externo);
    }
}
