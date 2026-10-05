<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AnonimizarPacienteAction;
use App\Actions\ExportarDadosPacienteAction;
use App\Enums\TipoModeloProntuario;
use App\Exceptions\ProntuarioImutavelException;
use App\Livewire\Prontuario;
use App\Livewire\ProntuarioModelos;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\PacienteAcesso;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\ProntuarioEvolucao;
use App\Models\ProntuarioFoto;
use App\Models\ProntuarioModelo;
use App\Models\ProntuarioOrientacao;
use App\Models\ProntuarioTermo;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Prontuário: evoluções, fotos, termos assinados, orientações e quem pode ver. */
class ProntuarioTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 válido, como o quadro de assinatura envia */
    private const ASSINATURA = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function paciente(string $nome = 'Maria Silva'): Paciente
    {
        return Paciente::create([
            'nome' => $nome, 'cpf' => $nome === 'Maria Silva' ? '123.456.789-00' : null, 'telefone' => '(61) 99999-0000',
        ])->fresh();
    }

    private function profissional(string $nome, ?User $user = null): Profissional
    {
        $p     = new Profissional(['nome' => $nome, 'email' => Str::slug($nome) . '@x.com', 'ativo' => true]);
        $p->id = (string) Str::uuid();
        $p->save();
        if ($user) {
            $p->forceFill(['user_id' => $user->id])->save();
        }

        return $p;
    }

    private function atender(Profissional $prof, Paciente $paciente): Agendamento
    {
        return Agendamento::create([
            'paciente_id'     => $paciente->id,
            'profissional_id' => $prof->id,
            'procedimento_id' => Procedimento::firstOrCreate(['nome' => 'Botox'], ['duracao_minutos' => 60, 'valor' => 800, 'ativo' => true])->id,
            'inicio_em'       => now()->subDay()->setTime(10, 0),
            'fim_em'          => now()->subDay()->setTime(11, 0),
            'status'          => 'realizado',
        ]);
    }

    public function test_so_admin_e_profissional_abrem_o_prontuario(): void
    {
        $paciente = $this->paciente();

        foreach (['recepcao', 'financeiro'] as $papel) {
            $this->actingAs(User::factory()->create(['role' => $papel]))
                ->get(route('pacientes.prontuario', $paciente->id))
                ->assertRedirect();
        }

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('pacientes.prontuario', $paciente->id))
            ->assertOk()->assertSee('Prontuário')->assertSee('Maria Silva');

        $this->assertSame(1, PacienteAcesso::where('paciente_id', $paciente->id)->where('acao', 'abriu_prontuario')->count());
    }

    public function test_profissional_so_ve_prontuario_dos_proprios_pacientes(): void
    {
        $user     = User::factory()->create(['role' => 'profissional']);
        $ana      = $this->profissional('Ana Souza', $user);
        $bia      = $this->profissional('Bia Lima');
        $meu      = $this->paciente('Paciente da Ana');
        $outro    = $this->paciente('Paciente da Bia');
        $this->atender($ana, $meu);
        $this->atender($bia, $outro);
        $fotoOutro = ProntuarioFoto::create([
            'paciente_id' => $outro->id, 'momento' => 'antes', 'tirada_em' => now()->toDateString(),
            'arquivo_path' => 'x.jpg', 'mime' => 'image/jpeg', 'tamanho' => 1,
        ]);

        $this->actingAs($user)->get(route('pacientes.prontuario', $meu->id))->assertOk();
        $this->actingAs($user)->get(route('pacientes.prontuario', $outro->id))->assertNotFound();
        $this->actingAs($user)->get(route('prontuario.foto', $fotoOutro->id))->assertNotFound();
    }

    public function test_evolucao_registrada_nao_pode_ser_alterada_nem_apagada(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $paciente = $this->paciente();
        $atend    = $this->atender($this->profissional('Ana Souza'), $paciente);
        $this->actingAs($admin);

        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->set('evolucaoTexto', 'Aplicação de toxina, 20U. Sem intercorrências.')
            ->set('evolucaoAgendamentoId', $atend->id)
            ->call('registrarEvolucao')
            ->assertHasNoErrors()
            ->assertSet('evolucaoTexto', '')
            ->assertSee('Aplicação de toxina, 20U.');

        $evolucao = ProntuarioEvolucao::sole();
        $this->assertSame($atend->profissional_id, $evolucao->profissional_id);
        $this->assertSame($admin->id, $evolucao->user_id);

        try {
            $evolucao->update(['texto' => 'mudado']);
            $this->fail('Evolução não deveria mudar');
        } catch (ProntuarioImutavelException) {
        }

        $this->expectException(ProntuarioImutavelException::class);
        $evolucao->delete();
    }

    public function test_atendimento_de_outro_paciente_nao_e_aceito(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paciente = $this->paciente();
        $outro    = $this->atender($this->profissional('Ana Souza'), $this->paciente('Outra'));

        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->set('evolucaoTexto', 'Texto da evolução')
            ->set('evolucaoAgendamentoId', $outro->id)
            ->call('registrarEvolucao')
            ->assertSet('flashErro', 'Esse atendimento não é deste paciente.');

        $this->assertSame(0, ProntuarioEvolucao::count());
    }

    public function test_fotos_ficam_privadas_com_miniatura_e_so_saem_pela_rota_protegida(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paciente = $this->paciente();

        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->set('aba', 'fotos')
            ->set('fotos', [UploadedFile::fake()->image('antes.jpg', 1200, 900), UploadedFile::fake()->image('antes2.png', 800, 800)])
            ->set('fotoMomento', 'antes')
            ->set('fotoRegiao', 'Testa')
            ->call('adicionarFotos')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', '2 fotos adicionadas.');

        $fotos = ProntuarioFoto::orderBy('created_at')->get();
        $this->assertCount(2, $fotos);
        $foto = $fotos->first();
        $this->assertStringStartsWith('clinicas/' . $this->clinica->id . '/prontuario/' . $paciente->id . '/fotos/', $foto->arquivo_path);
        Storage::disk('local')->assertExists($foto->arquivo_path);
        Storage::disk('local')->assertExists($foto->miniatura_path);
        [$largura] = getimagesizefromstring(Storage::disk('local')->get($foto->miniatura_path));
        $this->assertSame(480, $largura);

        $this->get(route('prontuario.foto', [$foto->id, 'mini']))->assertOk();
        $this->get(route('prontuario.foto', $foto->id))->assertOk();

        // Outra clínica não enxerga a foto
        $outra = $this->novaClinica();
        $userOutra = User::factory()->create(['role' => 'admin']);
        $userOutra->clinicas()->syncWithoutDetaching([$outra->id => ['papel' => 'admin']]);
        app(ClinicaAtual::class)->definir($outra);
        $this->assertNull(ProntuarioFoto::find($foto->id));
    }

    public function test_arquivo_que_nao_e_imagem_e_recusado(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(Prontuario::class, ['id' => $this->paciente()->id])
            ->set('fotos', [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')])
            ->call('adicionarFotos')
            ->assertHasErrors('fotos.0');

        $this->assertSame(0, ProntuarioFoto::count());
    }

    public function test_so_admin_remove_foto(): void
    {
        $user     = User::factory()->create(['role' => 'profissional']);
        $prof     = $this->profissional('Ana Souza', $user);
        $paciente = $this->paciente();
        $this->atender($prof, $paciente);
        Storage::disk('local')->put('f.jpg', 'x');
        $foto = ProntuarioFoto::create([
            'paciente_id' => $paciente->id, 'momento' => 'antes', 'tirada_em' => now()->toDateString(),
            'arquivo_path' => 'f.jpg', 'mime' => 'image/jpeg', 'tamanho' => 1,
        ]);

        $this->actingAs($user);
        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->call('removerFoto', $foto->id)
            ->assertSet('flashErro', 'Só administradores removem fotos do prontuário.');
        $this->assertNotNull(ProntuarioFoto::find($foto->id));

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->call('removerFoto', $foto->id)
            ->assertSet('flashSucesso', 'Foto removida.');
        $this->assertNull(ProntuarioFoto::find($foto->id));
        Storage::disk('local')->assertMissing('f.jpg');
    }

    public function test_termo_com_modelo_preenchido_e_assinado_vira_pdf(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paciente = $this->paciente();
        $atend    = $this->atender($this->profissional('Ana Souza'), $paciente);

        Livewire::test(ProntuarioModelos::class)->call('usarModelosProntos')->assertSet('flashErro', null);
        $modelo = ProntuarioModelo::where('tipo', TipoModeloProntuario::Termo)->sole();

        $tela = Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->call('abrirTermo')
            ->assertSet('termoAssinante', 'Maria Silva')
            ->set('termoAgendamentoId', $atend->id)
            ->set('termoModeloId', $modelo->id);

        $conteudo = $tela->get('termoConteudo');
        $this->assertStringContainsString('Eu, Maria Silva, CPF 123.456.789-00', $conteudo);
        $this->assertStringContainsString('procedimento Botox, a ser realizado por Ana Souza', $conteudo);
        $this->assertStringNotContainsString('{', $conteudo);

        // Sem assinatura válida, nada é salvo
        $tela->call('assinarTermo', 'nao-e-imagem')->assertHasErrors('assinatura');
        $this->assertSame(0, ProntuarioTermo::count());

        $tela->call('assinarTermo', self::ASSINATURA)
            ->assertHasNoErrors()
            ->assertSet('modalTermo', false)
            ->assertSet('aba', 'termos');

        $termo = ProntuarioTermo::sole();
        $this->assertSame(64, strlen($termo->hash));
        $this->assertSame($modelo->id, $termo->modelo_id);
        Storage::disk('local')->assertExists($termo->assinatura_path);

        $pdf = $this->get(route('prontuario.termo.pdf', $termo->id));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->expectException(ProntuarioImutavelException::class);
        $termo->update(['conteudo' => 'outro texto']);
    }

    public function test_orientacoes_salvas_e_enviadas_por_whatsapp(): void
    {
        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['ok' => true])]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paciente = $this->paciente();

        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->call('abrirOrientacao')
            ->assertSet('orientacaoWhatsapp', true)
            ->set('orientacaoTitulo', 'Cuidados após o peeling')
            ->set('orientacaoTexto', 'Evite sol por 7 dias.')
            ->call('salvarOrientacao')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'Orientações enviadas por WhatsApp.');

        $orientacao = ProntuarioOrientacao::sole();
        $this->assertNotNull($orientacao->enviada_whatsapp_em);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sendText') && $r['number'] === '5561999990000'
            && str_contains($r['textMessage']['text'], 'Evite sol por 7 dias.'));

        $this->get(route('prontuario.orientacao.pdf', $orientacao->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_paciente_anonimizado_mantem_prontuario_e_nao_recebe_registros(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paciente = $this->paciente();
        ProntuarioEvolucao::create(['paciente_id' => $paciente->id, 'texto' => 'Primeira sessão']);

        app(AnonimizarPacienteAction::class)->execute($paciente);

        $this->assertSame(1, ProntuarioEvolucao::count());
        Livewire::test(Prontuario::class, ['id' => $paciente->id])
            ->set('evolucaoTexto', 'Nova anotação')
            ->call('registrarEvolucao')
            ->assertSet('flashErro', 'Este paciente foi anonimizado. O prontuário fica guardado, mas não recebe novos registros.');
        $this->assertSame(1, ProntuarioEvolucao::count());
    }

    public function test_exportacao_lgpd_inclui_o_prontuario(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paciente = $this->paciente();
        ProntuarioEvolucao::create(['paciente_id' => $paciente->id, 'texto' => 'Primeira sessão']);
        ProntuarioOrientacao::create(['paciente_id' => $paciente->id, 'titulo' => 'Cuidados', 'texto' => 'Use protetor']);

        $dados = app(ExportarDadosPacienteAction::class)->execute($paciente);

        $this->assertSame('Primeira sessão', $dados['prontuario']['evolucoes'][0]['texto']);
        $this->assertSame('Cuidados', $dados['prontuario']['orientacoes'][0]['titulo']);
    }
}
