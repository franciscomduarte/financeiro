<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AnonimizarPacienteAction;
use App\Livewire\ConversaWhatsappPaciente;
use App\Livewire\PacienteIndex;
use App\Models\Lead;
use App\Models\Paciente;
use App\Models\PacienteMensagem;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** WhatsApp de quem já é paciente: não vira lead, fica na ficha e a recepção responde por lá. */
class ConversaPacienteTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $ana;

    protected function setUp(): void
    {
        parent::setUp();
        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc', 'whatsapp_numero' => '(61) 98888-0000']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['key' => ['id' => 'ENV1']], 201)]);
        $this->ana = Paciente::create(['nome' => 'Ana Paula', 'telefone' => '(61) 97777-2222']);
    }

    private function webhook(string $texto, string $id, bool $daClinica = false, string $jid = '5561977772222@s.whatsapp.net'): \Illuminate\Testing\TestResponse
    {
        app(ClinicaAtual::class)->definir(null);
        $resposta = $this->postJson('/api/whatsapp/webhook', [
            'event' => 'messages.upsert', 'instance' => 'lc',
            'data'  => ['key' => ['remoteJid' => $jid, 'fromMe' => $daClinica, 'id' => $id], 'pushName' => 'Ana', 'messageType' => 'conversation', 'message' => ['conversation' => $texto]],
        ]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());

        return $resposta;
    }

    public function test_mensagens_de_paciente_ficam_na_ficha_e_nao_viram_lead(): void
    {
        $this->webhook('Oi, posso remarcar?', 'P1')->assertJson(['status' => 'paciente']);
        $this->webhook('Oi, posso remarcar?', 'P1')->assertJson(['status' => 'paciente']); // reenvio do webhook não duplica
        $this->webhook('Claro! Qual dia?', 'C1', daClinica: true)->assertJson(['status' => 'paciente_resposta']);
        $this->webhook('Lembrete', 'C2', daClinica: true, jid: '556190000000@s.whatsapp.net')->assertJson(['status' => 'ignorado']);

        $this->assertSame(0, Lead::count());
        $mensagens = PacienteMensagem::orderBy('created_at')->get();
        $this->assertSame(['Oi, posso remarcar?', 'Claro! Qual dia?'], $mensagens->pluck('texto')->all());
        $this->assertSame([false, true], $mensagens->pluck('enviada')->all());
        $this->assertNull($mensagens[0]->lida_em);   // do paciente: nova
        $this->assertNotNull($mensagens[1]->lida_em); // da clínica: já lida
    }

    public function test_lead_marcado_como_ja_e_paciente_leva_a_conversa_para_a_ficha_mesmo_com_outro_numero(): void
    {
        Lead::create(['nome' => 'Ana (outro chip)', 'telefone' => '(61) 93333-2222', 'telefone_chave' => '6133332222',
            'origem' => 'whatsapp', 'etapa' => 'ja_paciente', 'paciente_id' => $this->ana->id]);

        $this->webhook('Sou eu de novo', 'P9', jid: '5561933332222@s.whatsapp.net')->assertJson(['status' => 'paciente']);

        $this->assertSame($this->ana->id, PacienteMensagem::sole()->paciente_id);
    }

    public function test_recepcao_ve_aviso_na_lista_le_e_responde_pela_ficha(): void
    {
        $this->webhook('Oi, posso remarcar?', 'P1');
        Paciente::create(['nome' => 'Bia Sem Mensagem', 'telefone' => '(61) 96666-1111']);
        $user = User::factory()->create(['role' => 'recepcao']);
        $this->actingAs($user);

        Livewire::test(PacienteIndex::class)
            ->assertSee('💬 1')
            ->set('soMensagensNovas', true)
            ->assertSee('Ana Paula')
            ->assertDontSee('Bia Sem Mensagem')
            ->call('abrirDetalhe', $this->ana->id)
            ->assertSeeLivewire(ConversaWhatsappPaciente::class);

        Livewire::test(ConversaWhatsappPaciente::class, ['pacienteId' => $this->ana->id])
            ->assertSee('Oi, posso remarcar?')
            ->set('resposta', '')->call('enviar')->assertHasErrors('resposta')
            ->set('resposta', 'Claro! Amanhã às 10h?')->call('enviar')
            ->assertHasNoErrors()->assertSet('resposta', '')->assertSet('flashErro', null)
            ->assertSee('Claro! Amanhã às 10h?');

        // Abrir a ficha marca como lida
        $this->assertNotNull(PacienteMensagem::where('mensagem_id', 'P1')->value('lida_em'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/message/sendText/lc') && $r['number'] === '5561977772222');
        $enviada = PacienteMensagem::where('mensagem_id', 'ENV1')->sole();
        $this->assertSame($user->id, $enviada->user_id);

        // O eco do webhook não duplica a resposta
        $this->webhook('Claro! Amanhã às 10h?', 'ENV1', daClinica: true);
        $this->assertSame(2, PacienteMensagem::count());
    }

    public function test_perfil_somente_consulta_ve_mas_nao_responde_nem_marca_como_lida(): void
    {
        $this->webhook('Oi', 'P1');
        $this->actingAs(User::factory()->create(['role' => 'consulta']));
        $this->get('/pacientes')->assertOk();

        Livewire::test(ConversaWhatsappPaciente::class, ['pacienteId' => $this->ana->id])
            ->assertSee('Oi')
            ->assertDontSee('Escreva a resposta')
            ->set('resposta', 'tentando')->call('enviar')
            ->assertSet('flashErro', 'Seu perfil é somente consulta: dá para ver, mas não criar, alterar ou excluir. Fale com o administrador da clínica.');

        $this->assertNull(PacienteMensagem::sole()->lida_em);
        Http::assertNothingSent();
    }

    public function test_anonimizar_apaga_a_conversa_e_o_endereco(): void
    {
        $this->webhook('Oi', 'P1');
        $this->ana->update(['cep' => '71900100', 'logradouro' => 'Rua 12', 'numero' => '3', 'bairro' => 'Centro', 'cidade' => 'Brasília', 'uf' => 'DF', 'codigo_municipio' => '5300108']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        app(AnonimizarPacienteAction::class)->execute($this->ana->fresh());

        $this->assertSame(0, PacienteMensagem::count());
        $ana = $this->ana->fresh();
        $this->assertNull($ana->cep);
        $this->assertNull($ana->logradouro);
        $this->assertNull($ana->codigo_municipio);
    }
}
