<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Atendimento\FinalizarAtendimentoAction;
use App\Actions\Atendimento\IniciarAtendimentoAction;
use App\Actions\Atendimento\SalvarRespostaAtendimentoAction;
use App\Enums\StatusAtendimento;
use App\Livewire\AgendamentoIndex;
use App\Livewire\AtendimentoTela;
use App\Livewire\FichaModelos;
use App\Livewire\Prontuario;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\AtendimentoFicha;
use App\Models\FichaModelo;
use App\Models\Orcamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\StockBatch;
use App\Models\StockCategory;
use App\Models\StockProduct;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** Módulo de atendimento: fichas, salvamento automático, injetáveis com baixa, plano e privacidade. */
class AtendimentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Paciente $maria;
    private Agendamento $horario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);

        $this->maria = Paciente::create(['nome' => 'Maria Silva', 'telefone' => '(61) 99999-0000'])->fresh();
        $ana         = new Profissional(['nome' => 'Ana Souza', 'email' => 'ana@x.com', 'ativo' => true]);
        $ana->id     = (string) Str::uuid();
        $ana->save();
        $this->horario = Agendamento::create([
            'paciente_id' => $this->maria->id, 'profissional_id' => $ana->id,
            'procedimento_id' => Procedimento::create(['nome' => 'Toxina', 'duracao_minutos' => 30, 'valor' => 900, 'ativo' => true])->id,
            'inicio_em' => now()->addHour(), 'fim_em' => now()->addHours(2), 'status' => 'confirmado',
        ]);
    }

    private function iniciar(): Atendimento
    {
        return app(IniciarAtendimentoAction::class)->execute($this->maria->id, $this->horario->id);
    }

    private function anamnese(): FichaModelo
    {
        return FichaModelo::query()->where('nome', 'Anamnese')->sole();
    }

    private function campo(FichaModelo $m, string $rotulo): string
    {
        return collect($m->campos)->firstWhere('rotulo', $rotulo)['id'];
    }

    public function test_iniciar_pela_agenda_cria_fichas_padrao_e_retoma_o_mesmo_atendimento(): void
    {
        Livewire::test(AgendamentoIndex::class)
            ->call('iniciarAtendimento', $this->horario->id)
            ->assertRedirect(route('atendimentos.show', Atendimento::sole()->id));

        $this->assertSame(7, FichaModelo::count());
        $this->assertSame($this->horario->profissional_id, Atendimento::sole()->profissional_id);
        $this->assertSame(Atendimento::sole()->id, $this->iniciar()->id); // não duplica

        $this->get(route('atendimentos.show', Atendimento::sole()->id))
            ->assertOk()->assertSee('Anamnese')->assertSee('Queixa principal')->assertSee('Plano de tratamento')->assertSee('Finalizar atendimento');
    }

    public function test_respostas_salvam_sozinhas_e_sao_limpas(): void
    {
        $a = $this->iniciar();
        $m = $this->anamnese();

        Livewire::test(AtendimentoTela::class, ['id' => $a->id])
            ->call('salvarTextoRico', $m->id, $this->campo($m, 'Queixa principal'), '<p style="text-align:center" onclick="x()"><strong>Manchas</strong> no rosto<script>alert(1)</script></p>')
            ->set("respostas.{$m->id}.{$this->campo($m, 'Condições de saúde')}", ['Diabetes', 'Inventada'])
            ->set("respostas.{$m->id}.{$this->campo($m, 'Fumante')}", 'sim')
            ->assertSet("respostas.{$m->id}.{$this->campo($m, 'Condições de saúde')}", ['Diabetes'])
            ->assertSet('flashErro', null);

        $ficha = AtendimentoFicha::sole();
        $this->assertSame('Anamnese', $ficha->titulo);
        $this->assertSame('<p style="text-align: center"><strong>Manchas</strong> no rosto</p>', $ficha->respostas[$this->campo($m, 'Queixa principal')]);
        $this->assertSame('sim', $ficha->respostas[$this->campo($m, 'Fumante')]);
        $this->assertSame(['Diabetes'], $ficha->respostas[$this->campo($m, 'Condições de saúde')]);
        $this->assertSame(1, AtendimentoFicha::query()->whereRaw("respostas @> ?::jsonb", [json_encode([$this->campo($m, 'Fumante') => 'sim'])])->count());
    }

    public function test_finalizar_baixa_estoque_tranca_e_leva_para_a_conclusao(): void
    {
        $categoria = StockCategory::create(['name' => 'Injetáveis']);
        $toxina    = StockProduct::create(['name' => 'Toxina 100U', 'category_id' => $categoria->id, 'unit_type' => 'UI', 'unit_cost' => 10, 'active' => true]);
        $lote      = app(StockService::class)->purchaseEntry($toxina->id, 'L-123', 100, 1000, now()->addYear()->toDateString(), now()->toDateString());

        $a = $this->iniciar();
        $m = $this->anamnese();

        Livewire::test(AtendimentoTela::class, ['id' => $a->id])
            ->call('salvarTextoRico', $m->id, $this->campo($m, 'Queixa principal'), '<p>Rugas na testa</p>')
            ->set('injProduto', (string) $toxina->id)
            ->set('injQuantidade', '120')->call('adicionarInjetavel')
            ->set('injQuantidade', '20')->set('injRegiao', 'Glabela')->call('adicionarInjetavel')
            ->tap(fn ($t) => $t->call('removerInjetavel', $a->injetaveis()->where('quantidade', 120)->value('id')))
            ->call('finalizar')
            ->assertRedirect(route('agenda.index', ['concluir' => $this->horario->id]));

        $a->refresh();
        $this->assertSame(StatusAtendimento::Finalizado, $a->status);
        $this->assertNotNull($a->duracao_segundos);
        $this->assertEquals(80, (float) StockBatch::find($lote->id)->quantity_available);
        $this->assertSame('L-123', $a->injetaveis()->sole()->lotes_baixados);

        // Trancado: não aceita mais respostas
        try {
            app(SalvarRespostaAtendimentoAction::class)->execute($a->id, $m->id, $this->campo($m, 'Fumante'), 'sim');
            $this->fail('Deveria recusar');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('já foi finalizado', $e->getMessage());
        }

        // A agenda abre a conclusão (receita/pacote) do horário
        Livewire::withQueryParams(['concluir' => $this->horario->id])->test(AgendamentoIndex::class)->assertSet('concluirId', $this->horario->id);

        // Histórico no prontuário
        Livewire::test(Prontuario::class, ['id' => $this->maria->id])->assertSee('Rugas na testa')->assertSee('Toxina 100U 20');
    }

    public function test_estoque_insuficiente_impede_finalizar(): void
    {
        $categoria = StockCategory::create(['name' => 'Injetáveis']);
        $produto   = StockProduct::create(['name' => 'Ácido hialurônico', 'category_id' => $categoria->id, 'unit_type' => 'ml', 'active' => true]);
        app(StockService::class)->purchaseEntry($produto->id, 'AH-1', 1, 500, now()->addYear()->toDateString(), now()->toDateString());

        $a = $this->iniciar();
        Livewire::test(AtendimentoTela::class, ['id' => $a->id])
            ->set('injProduto', (string) $produto->id)->set('injQuantidade', '2')->call('adicionarInjetavel')
            ->call('finalizar')
            ->assertSee('Estoque de Ácido hialurônico');

        $this->assertTrue($a->fresh()->emAndamento());
    }

    public function test_plano_vira_orcamento_e_cancelar_descarta(): void
    {
        $a    = $this->iniciar();
        $proc = Procedimento::create(['nome' => 'Peeling', 'duracao_minutos' => 40, 'valor' => 250, 'ativo' => true, 'retorno_dias' => 21]);

        Livewire::test(AtendimentoTela::class, ['id' => $a->id])
            ->call('adicionarItemPlano')
            ->set('planoItens.0.procedimento_id', (string) $proc->id)
            ->assertSet('planoItens.0.descricao', 'Peeling')
            ->assertSet('planoItens.0.intervalo_dias', '21')
            ->set('planoItens.0.sessoes', '4')
            ->call('gerarOrcamento')
            ->assertSet('flashErro', null);

        $orcamento = Orcamento::with('itens')->sole();
        $this->assertEquals(1000, (float) $orcamento->total);
        $this->assertSame(4, $orcamento->itens->sole()->quantidade);
        $this->assertSame($orcamento->id, $a->plano()->sole()->orcamento_id);

        // Com orçamento gerado, não dá para cancelar (só finalizar)
        Livewire::test(AtendimentoTela::class, ['id' => $a->id])->call('cancelar')->assertSee('Finalize o atendimento em vez de cancelar');

        $outro = app(IniciarAtendimentoAction::class)->execute($this->maria->id);
        Livewire::test(AtendimentoTela::class, ['id' => $outro->id])->call('cancelar')->assertRedirect(route('pacientes.prontuario', $this->maria->id));
        $this->assertNull(Atendimento::find($outro->id));
    }

    public function test_privado_so_o_autor_e_admin_veem(): void
    {
        $profUser = User::factory()->create(['role' => 'profissional']);
        Profissional::query()->whereKey($this->horario->profissional_id)->update(['user_id' => $profUser->id]);
        $outroProf = User::factory()->create(['role' => 'profissional']);
        $outraProfissional = new Profissional(['nome' => 'Bia', 'email' => 'bia@x.com', 'ativo' => true]);
        $outraProfissional->id = (string) Str::uuid();
        $outraProfissional->save();
        $outraProfissional->forceFill(['user_id' => $outroProf->id])->save();
        Agendamento::create(['paciente_id' => $this->maria->id, 'profissional_id' => $outraProfissional->id, 'procedimento_id' => $this->horario->procedimento_id,
            'inicio_em' => now()->subDays(3), 'fim_em' => now()->subDays(3)->addHour(), 'status' => 'realizado']);

        $this->actingAs($profUser);
        $a = $this->iniciar();
        $this->assertSame('privado', $a->visibilidade->value);

        $this->actingAs($outroProf);
        $this->get(route('atendimentos.show', $a->id))->assertNotFound();

        $this->actingAs($profUser);
        app(FinalizarAtendimentoAction::class)->alterarVisibilidade($a->id, \App\Enums\VisibilidadeAtendimento::Equipe);

        $this->actingAs($outroProf);
        $this->get(route('atendimentos.show', $a->id))->assertOk()->assertSee('Só quem iniciou o atendimento pode preenchê-lo.');

        $this->actingAs(User::factory()->create(['role' => 'recepcao']))->get(route('atendimentos.show', $a->id))->assertRedirect();
    }

    public function test_clinica_edita_as_fichas(): void
    {
        Livewire::test(FichaModelos::class)
            ->assertSee('Ficha de Ozonioterapia')
            ->call('novo')
            ->set('nome', 'Pós-procedimento')
            ->set('campos.0.rotulo', 'Como está a região?')
            ->call('adicionarCampo')
            ->set('campos.1.tipo', 'escolha')
            ->set('campos.1.rotulo', 'Dor')
            ->set('campos.1.opcoes', "Sem dor")
            ->call('salvar')
            ->assertSee('Coloque pelo menos 2 opções em "Dor"')
            ->set('campos.1.opcoes', "Sem dor\nLeve\nForte")
            ->call('salvar')
            ->assertSet('modal', false);

        $m = FichaModelo::query()->where('nome', 'Pós-procedimento')->sole();
        $this->assertSame(['Sem dor', 'Leve', 'Forte'], $m->campos[1]['opcoes']);

        // Atendimento já respondido guarda a cópia: mudar o modelo não altera o registro
        $a    = $this->iniciar();
        $idQ  = $m->campos[0]['id'];
        app(SalvarRespostaAtendimentoAction::class)->execute($a->id, $m->id, $idQ, 'Sem inchaço');
        Livewire::test(FichaModelos::class)->call('editar', $m->id)->set('campos.0.rotulo', 'Mudou')->call('salvar');

        $this->assertSame('Como está a região?', AtendimentoFicha::sole()->campos[0]['rotulo']);
        $this->assertSame($idQ, $m->fresh()->campos[0]['id']); // id da pergunta se mantém
    }

    public function test_anexa_pdf_no_atendimento_e_aparece_no_prontuario(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $a = $this->iniciar();

        Livewire::test(AtendimentoTela::class, ['id' => $a->id])
            ->call('irPara', 'fotos')
            ->set('anexos', [\Illuminate\Http\UploadedFile::fake()->image('foto.jpg')])
            ->call('adicionarAnexos')
            ->assertHasErrors('anexos.0');

        Livewire::test(AtendimentoTela::class, ['id' => $a->id])
            ->call('irPara', 'fotos')
            ->set('anexos', [\Illuminate\Http\UploadedFile::fake()->createWithContent('hemograma.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n")])
            ->set('anexoDescricao', 'Hemograma 10/2026')
            ->call('adicionarAnexos')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'PDF anexado ao prontuário.')
            ->assertSee('Hemograma 10/2026');

        $anexo = \App\Models\ProntuarioAnexo::sole();
        $this->assertSame($a->id, $anexo->atendimento_id);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($anexo->arquivo_path);

        $this->get(route('prontuario.anexo', $anexo->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Livewire::test(Prontuario::class, ['id' => $this->maria->id])->set('aba', 'fotos')->assertSee('Hemograma 10/2026');

        // Atendimento privado: outro profissional não baixa o PDF
        $outro = User::factory()->create(['role' => 'profissional']);
        $this->actingAs($outro)->get(route('prontuario.anexo', $anexo->id))->assertNotFound();

        // Quem atendeu remove enquanto está em andamento; o arquivo some junto
        $this->actingAs($this->admin);
        Livewire::test(AtendimentoTela::class, ['id' => $a->id])->call('removerAnexo', $anexo->id)->assertSet('flashErro', null);
        $this->assertSame(0, \App\Models\ProntuarioAnexo::count());
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($anexo->arquivo_path);
    }
}
