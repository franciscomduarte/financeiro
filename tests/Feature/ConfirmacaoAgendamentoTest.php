<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrigemConfirmacao;
use App\Enums\StatusAgendamento;
use App\Jobs\DespacharNotificacoesJob;
use App\Livewire\AgendamentoIndex;
use App\Models\Agendamento;
use App\Models\AssistenteConfiguracao;
use App\Models\Paciente;
use App\Models\PacienteMensagem;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AgendamentoService;
use App\Support\ClinicaAtual;
use App\Support\RespostaConfirmacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Véspera: o lembrete pede confirmação no WhatsApp; SIM confirma na agenda e NÃO avisa a equipe para remarcar. */
class ConfirmacaoAgendamentoTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $ana;
    private Agendamento $agendamento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 5)->setTime(9, 0));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc', 'whatsapp_numero' => '(61) 98888-0000']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['key' => ['id' => 'MSG-' . Str::random(6)]], 201)]);
        Mail::fake();
        AssistenteConfiguracao::create(['ativo' => true, 'nome' => 'Assistente']);

        $this->ana = Paciente::create(['nome' => 'Ana Paula', 'telefone' => '(61) 97777-2222']);
        $prof = new Profissional(['nome' => 'Lara Cristian', 'email' => 'lara@x.com', 'ativo' => true]);
        $prof->id = (string) Str::uuid();
        $prof->save();
        $proc = Procedimento::create(['nome' => 'Limpeza de pele', 'duracao_minutos' => 60, 'valor' => 200, 'ativo' => true]);
        $this->agendamento = app(AgendamentoService::class)->criar([
            'paciente_id' => $this->ana->id, 'profissional_id' => $prof->id, 'procedimento_id' => $proc->id, 'inicio_em' => '2026-10-08 14:00',
        ]);
    }

    private function enviarVespera(): void
    {
        $this->travelTo(now()->setTimezone('America/Sao_Paulo')->setDate(2026, 10, 7)->setTime(14, 1));
        (new DespacharNotificacoesJob())->handle();
    }

    private function webhook(string $texto, string $id, string $jid = '5561977772222@s.whatsapp.net'): \Illuminate\Testing\TestResponse
    {
        app(ClinicaAtual::class)->definir(null);
        $resposta = $this->postJson('/api/whatsapp/webhook', [
            'event' => 'messages.upsert', 'instance' => 'lc',
            'data'  => ['key' => ['remoteJid' => $jid, 'fromMe' => false, 'id' => $id], 'pushName' => 'Ana', 'messageType' => 'conversation', 'message' => ['conversation' => $texto]],
        ]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());

        return $resposta;
    }

    public function test_lembrete_da_vespera_pede_confirmacao_e_sim_confirma_na_agenda(): void
    {
        $this->enviarVespera();

        $this->assertNotNull($this->agendamento->fresh()->confirmacao_pedida_em);
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'Responda *SIM* para confirmar'));

        $this->webhook('Sim, confirmo! 😊', 'R1')->assertJson(['status' => 'paciente']);

        $ag = $this->agendamento->fresh();
        $this->assertSame(StatusAgendamento::Confirmado, $ag->status);
        $this->assertSame(OrigemConfirmacao::Paciente, $ag->confirmacao_origem);
        $this->assertNotNull($ag->confirmado_em);
        $this->assertSame('Sim, confirmo! 😊', $ag->resposta_confirmacao);

        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'Seu horário de Limpeza de pele amanhã às 14:00 está confirmado'));
        Http::assertNotSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'em breve alguém te responde')); // sem a resposta genérica
        Http::assertNotSent(fn ($r) => ($r['number'] ?? '') === '5561988880000'); // confirmação não incomoda a equipe
        $this->assertTrue(PacienteMensagem::where('enviada', true)->where('do_assistente', true)->where('texto', 'like', '%está confirmado%')->exists());

        // SIM de novo não repete o agradecimento
        $enviadas = count(Http::recorded());
        $this->webhook('sim', 'R2');
        $this->assertCount($enviadas, Http::recorded());

        Livewire::test(AgendamentoIndex::class)->set('filtroData', '2026-10-08')->set('visao', 'lista')
            ->assertSee('pelo WhatsApp');
    }

    public function test_nao_marca_pediu_para_remarcar_e_avisa_a_equipe(): void
    {
        $this->enviarVespera();

        $this->webhook('Não vou conseguir ir, podemos remarcar?', 'R1');

        $ag = $this->agendamento->fresh();
        $this->assertSame(StatusAgendamento::Agendado, $ag->status);
        $this->assertNotNull($ag->pediu_remarcar_em);
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'Já avisei a nossa equipe'));
        Http::assertSent(fn ($r) => ($r['number'] ?? '') === '5561988880000'
            && str_contains($r['textMessage']['text'] ?? '', 'Ana Paula respondeu ao pedido de confirmação e não vai poder vir amanhã'));

        Livewire::test(AgendamentoIndex::class)->set('filtroData', '2026-10-08')->set('visao', 'lista')
            ->assertSee('1 paciente pediu para remarcar')
            ->assertSee('Não vou conseguir ir, podemos remarcar?')
            ->call('confirmarAgendamento', $ag->id)
            ->assertDontSee('1 paciente pediu para remarcar');

        $ag = $ag->fresh();
        $this->assertSame(StatusAgendamento::Confirmado, $ag->status);
        $this->assertSame(OrigemConfirmacao::Equipe, $ag->confirmacao_origem);
        $this->assertNull($ag->pediu_remarcar_em);
    }

    public function test_sim_sem_pedido_de_confirmacao_segue_o_fluxo_normal(): void
    {
        $this->webhook('Sim', 'R1'); // lembrete ainda não saiu

        $this->assertSame(StatusAgendamento::Agendado, $this->agendamento->fresh()->status);
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'em breve alguém te responde'));
    }

    public function test_outra_mensagem_depois_do_pedido_nao_confirma(): void
    {
        $this->enviarVespera();

        $this->webhook('Qual o endereço mesmo?', 'R1');

        $ag = $this->agendamento->fresh();
        $this->assertSame(StatusAgendamento::Agendado, $ag->status);
        $this->assertNull($ag->pediu_remarcar_em);
    }

    public function test_mudar_o_horario_desfaz_a_confirmacao(): void
    {
        $this->enviarVespera();
        $this->webhook('sim', 'R1');
        $this->assertSame(StatusAgendamento::Confirmado, $this->agendamento->fresh()->status);

        $this->agendamento->fresh()->update(['inicio_em' => '2026-10-09 10:00', 'fim_em' => '2026-10-09 11:00']);

        $ag = $this->agendamento->fresh();
        $this->assertSame(StatusAgendamento::Agendado, $ag->status);
        $this->assertNull($ag->confirmado_em);
        $this->assertNull($ag->confirmacao_pedida_em);
    }

    public function test_interpreta_as_respostas(): void
    {
        foreach (['Sim', 'SIM!', 's', 'Confirmo', 'confirmado, obrigada', 'Ok', '👍', 'Estarei aí', 'Com certeza!'] as $sim) {
            $this->assertTrue(RespostaConfirmacao::interpretar($sim), $sim);
        }
        foreach (['Não', 'nao', 'N', 'Preciso remarcar', 'Sim, mas preciso desmarcar', 'Vou ter que cancelar', 'Tive um imprevisto'] as $nao) {
            $this->assertFalse(RespostaConfirmacao::interpretar($nao), $nao);
        }
        foreach (['Qual o endereço?', 'Bom dia', '😊', ''] as $outro) {
            $this->assertNull(RespostaConfirmacao::interpretar($outro), $outro);
        }
    }
}
