<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusClinica;
use App\Livewire\PacienteIndex;
use App\Models\Paciente;
use App\Models\PacienteAcesso;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** LGPD: consentimento, registro de acessos, exportação e anonimização de pacientes. */
class LgpdPacienteTest extends TestCase
{
    use RefreshDatabase;

    private function paciente(): Paciente
    {
        return Paciente::create([
            'nome' => 'Maria Silva', 'cpf' => '123.456.789-00', 'telefone' => '(61) 99999-0000',
            'email' => 'maria@x.com', 'anamnese' => 'Alergia a lidocaína', 'observacoes' => 'Prefere manhã',
        ]);
    }

    public function test_consentimento_guarda_data_e_quem_registrou(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $paciente = $this->paciente();
        $this->actingAs($admin);

        Livewire::test(PacienteIndex::class)
            ->call('abrirModalEditar', $paciente->id)
            ->set('consentimento', true)->set('aceitaWhatsappMarketing', true)
            ->call('atualizar')->assertHasNoErrors();

        $p = $paciente->fresh();
        $this->assertNotNull($p->consentimento_em);
        $this->assertSame($admin->id, $p->consentimento_por);
        $this->assertTrue($p->aceita_whatsapp_marketing);
        $this->assertFalse($p->aceita_email_marketing);
    }

    public function test_abrir_e_editar_ficam_registrados_sem_repetir_a_cada_clique(): void
    {
        $user     = User::factory()->create(['role' => 'recepcao']);
        $paciente = $this->paciente();
        $this->actingAs($user);

        Livewire::test(PacienteIndex::class)
            ->call('abrirDetalhe', $paciente->id)
            ->call('fecharModais')
            ->call('abrirDetalhe', $paciente->id)
            ->call('abrirModalEditar', $paciente->id)
            ->call('atualizar');

        $acoes = PacienteAcesso::where('paciente_id', $paciente->id)->orderBy('id')->pluck('acao')->map->value->all();
        $this->assertSame(['visualizou', 'editou'], $acoes);
        $this->assertSame($user->id, PacienteAcesso::first()->user_id);
    }

    public function test_admin_ve_quem_acessou(): void
    {
        $paciente = $this->paciente();
        $this->actingAs(User::factory()->create(['role' => 'recepcao', 'name' => 'Rita Recepção']));
        Livewire::test(PacienteIndex::class)->call('abrirDetalhe', $paciente->id);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(PacienteIndex::class)->call('abrirDetalhe', $paciente->id)
            ->assertSee('Quem acessou estes dados')->assertSee('Rita Recepção');
    }

    public function test_acesso_e_registrado_mesmo_em_modo_somente_leitura(): void
    {
        $paciente = $this->paciente();
        $this->clinica->update(['status' => StatusClinica::Teste, 'teste_ate' => today()->subDay()]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(\App\Support\ClinicaAtual::class)->definir($this->clinica->fresh());

        Livewire::test(PacienteIndex::class)->call('abrirDetalhe', $paciente->id);

        $this->assertSame(1, PacienteAcesso::count());
    }

    public function test_exportar_gera_arquivo_com_os_dados_e_registra(): void
    {
        $paciente = $this->paciente();
        Transacao::factory()->create(['paciente_id' => $paciente->id, 'descricao' => 'Botox']);

        $resposta = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('pacientes.exportar', $paciente->id))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="dados-maria-silva-' . now()->format('Y-m-d') . '.json"');

        $dados = $resposta->json();
        $this->assertSame('Maria Silva', $dados['paciente']['nome']);
        $this->assertSame('Alergia a lidocaína', $dados['paciente']['anamnese']);
        $this->assertSame('Botox', $dados['pagamentos'][0]['descricao']);
        $this->assertTrue(PacienteAcesso::where('acao', 'exportou')->exists());
    }

    public function test_so_admin_exporta(): void
    {
        $paciente = $this->paciente();

        $this->actingAs(User::factory()->create(['role' => 'recepcao']))
            ->get(route('pacientes.exportar', $paciente->id))->assertForbidden();
    }

    public function test_anonimizar_apaga_dados_pessoais_e_mantem_o_financeiro(): void
    {
        Storage::fake('public');
        $paciente = $this->paciente();
        $foto     = UploadedFile::fake()->image('f.jpg')->store('pacientes', 'public');
        $paciente->forceFill(['foto_path' => $foto, 'consentimento_em' => now()])->save();
        $lancamento = Transacao::factory()->create(['paciente_id' => $paciente->id, 'valor_bruto' => 800]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(PacienteIndex::class)
            ->call('abrirDetalhe', $paciente->id)
            ->call('anonimizar')
            ->assertSet('flashSucesso', fn ($m) => str_contains((string) $m, 'apagados'));

        $p = $paciente->fresh();
        $this->assertStringStartsWith('Paciente anonimizado', $p->nome);
        foreach (['cpf', 'telefone', 'email', 'anamnese', 'observacoes', 'foto_path'] as $campo) {
            $this->assertNull($p->{$campo}, $campo);
        }
        $this->assertNotNull($p->anonimizado_em);
        Storage::disk('public')->assertMissing($foto);
        $this->assertSame('800.00', $lancamento->fresh()->valor_bruto);
        $this->assertSame($p->id, $lancamento->fresh()->paciente_id);
        $this->assertTrue(PacienteAcesso::where('acao', 'anonimizou')->exists());
    }

    public function test_so_admin_anonimiza(): void
    {
        $paciente = $this->paciente();
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));

        Livewire::test(PacienteIndex::class)->call('abrirDetalhe', $paciente->id)->call('anonimizar')->assertForbidden();
        $this->assertSame('Maria Silva', $paciente->fresh()->nome);
    }
}
