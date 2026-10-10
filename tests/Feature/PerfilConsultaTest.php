<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendamentoIndex;
use App\Livewire\LeadIndex;
use App\Livewire\PacienteIndex;
use App\Models\Lead;
use App\Models\Paciente;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** Perfil "Somente consulta": vê agenda, pacientes e leads, sem gravar nada. */
class PerfilConsultaTest extends TestCase
{
    use RefreshDatabase;

    private const AVISO = 'Seu perfil é somente consulta: dá para ver, mas não criar, alterar ou excluir. Fale com o administrador da clínica.';

    private Paciente $paciente;
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paciente = Paciente::create(['nome' => 'Carla Souza', 'telefone' => '(61) 99999-0000']);
        $this->lead     = Lead::create(['nome' => 'Joana', 'telefone' => '(61) 98888-0000', 'etapa' => 'novo', 'origem' => 'instagram']);
        $this->actingAs(User::factory()->create(['role' => 'consulta']));

        // A primeira página passa pelo middleware, que marca a requisição como somente consulta
        $this->get('/pacientes')->assertOk()->assertSee('Carla Souza')->assertSee('Somente consulta:');
    }

    public function test_ve_as_telas_sem_os_botoes_de_criar_e_editar(): void
    {
        $this->assertTrue(app(ClinicaAtual::class)->somenteLeitura());

        Livewire::test(PacienteIndex::class)->assertSee('Carla Souza')->assertDontSee('Novo paciente')->assertDontSee('aria-label="Editar Carla Souza"', false);
        Livewire::test(AgendamentoIndex::class)->assertDontSee('wire:click="abrirModalCriar"', false)->assertDontSee('abrirModalNovoBloqueio(', false);
        Livewire::test(LeadIndex::class)->assertDontSee('+ Novo lead');
    }

    public function test_nao_abre_formulario_nem_grava(): void
    {
        Livewire::test(PacienteIndex::class)
            ->call('abrirModalCriar')
            ->assertSet('modalCriar', false)
            ->assertSet('flashErro', self::AVISO)
            ->call('abrirModalEditar', $this->paciente->id)
            ->assertSet('modalEditar', false);

        Livewire::test(AgendamentoIndex::class)
            ->call('novoNoHorario', now()->addDay()->toDateString(), '10:00')
            ->assertSet('modalCriar', false)
            ->assertSet('flashErro', self::AVISO);

        // Mesmo chamando a gravação direto, nada muda no banco
        Livewire::test(PacienteIndex::class)
            ->set('nome', 'Invasor')
            ->call('salvar')
            ->assertSet('flashErro', self::AVISO);
        $this->assertFalse(Paciente::where('nome', 'Invasor')->exists());
    }

    public function test_nao_mexe_em_lead_nem_manda_whatsapp(): void
    {
        Http::fake();
        $this->clinica->forceFill(['evolution_instance' => 'clinica', 'evolution_api_key' => 'chave'])->save();
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        $lead = $this->lead;

        Livewire::test(LeadIndex::class)
            ->call('abrir', $lead->id)
            ->assertSee('Joana')
            ->call('mover', $lead->id, 'em_contato')
            ->set('resposta', 'Oi')
            ->call('enviarWhatsApp');

        $this->assertSame('novo', $lead->fresh()->etapa->value);
        Http::assertNothingSent();
    }

    public function test_formularios_http_e_api_sao_recusados(): void
    {
        $this->postJson('/api/v1/transacoes', [])->assertStatus(403)->assertJson(['message' => self::AVISO]);
    }
}
