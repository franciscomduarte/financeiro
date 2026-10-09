<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\NotaFiscal\MontarNotaFocus;
use App\Actions\NotaFiscal\RevisarNotasProcessandoAction;
use App\Enums\PadraoNfse;
use App\Enums\StatusNotaFiscal;
use App\Jobs\ConsultarNotaFiscalJob;
use App\Jobs\EnviarNotaFiscalJob;
use App\Livewire\ConfiguracaoClinica;
use App\Livewire\NotaFiscalIndex;
use App\Livewire\PacienteIndex;
use App\Models\NotaFiscal;
use App\Models\Paciente;
use App\Models\Transacao;
use App\Models\User;
use App\Support\ClinicaAtual;
use App\Support\Documento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** NFS-e: aviso automático da Focus, varredura de notas presas, CPF válido e endereço do tomador. */
class NotaFiscalAvisoEnderecoTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;
    private Transacao $receita;

    private const VIACEP = [
        'cep' => '71900-100', 'logradouro' => 'Rua 12', 'bairro' => 'Águas Claras', 'localidade' => 'Brasília', 'uf' => 'DF', 'ibge' => '5300108',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->paciente = Paciente::create(['nome' => 'Maria Silva', 'cpf' => '123.456.789-09', 'email' => 'maria@exemplo.com'])->fresh();
        $this->receita  = Transacao::factory()->create([
            'tipo' => 'entrada', 'status' => 'pago', 'valor_bruto' => 350, 'descricao' => 'Atendimento: Limpeza de pele',
            'paciente_id' => $this->paciente->id, 'cliente' => 'Maria Silva', 'data_competencia' => now()->subDays(3)->toDateString(),
        ]);
        $this->clinica->update([
            'cnpj' => '12.345.678/0001-90', 'nfse_token' => 'token-teste', 'nfse_homologacao' => true,
            'inscricao_municipal' => '0812345', 'codigo_municipio' => '5300108', 'nfse_item_lista_servico' => '06.02',
            'nfse_aliquota_iss' => 2, 'nfse_optante_simples' => true,
        ]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
    }

    private function nota(array $atributos = []): NotaFiscal
    {
        return NotaFiscal::create([
            'transacao_id' => $this->receita->id, 'paciente_id' => $this->paciente->id, 'referencia' => 'nf-' . uniqid(),
            'status' => StatusNotaFiscal::Processando, 'homologacao' => true, 'padrao' => PadraoNfse::Municipal,
            'valor' => 350, 'discriminacao' => 'Limpeza de pele', 'tomador_nome' => 'Maria Silva', ...$atributos,
        ]);
    }

    // ─── Aviso automático (gatilho da Focus) ─────────────────────

    public function test_ativar_aviso_cadastra_o_gatilho_na_focus_e_mostra_ativo(): void
    {
        Http::fake(['homologacao.focusnfe.com.br/v2/hooks' => Http::response(['id' => 'h1'], 201)]);

        Livewire::test(ConfiguracaoClinica::class)->set('aba', 'nota_fiscal')
            ->assertSee('Ativar aviso automático')
            ->call('ativarAvisoNfse')
            ->assertSet('flashErro', null)
            ->assertSee('Ativo desde');

        $clinica = $this->clinica->fresh();
        $this->assertTrue($clinica->avisoNfseAtivo());
        Http::assertSent(fn (Request $r) => $r->url() === 'https://homologacao.focusnfe.com.br/v2/hooks'
            && $r['event'] === 'nfse' && $r['cnpj'] === '12345678000190'
            && $r['url'] === route('webhook.nfse') && $r['authorization'] === $clinica->nfse_webhook_token);

        // Ao passar para produção, o aviso precisa ser ativado de novo
        $clinica->update(['nfse_homologacao' => false]);
        $this->assertFalse($clinica->fresh()->avisoNfseAtivo());
    }

    public function test_focus_recusando_o_gatilho_mostra_o_motivo(): void
    {
        Http::fake(['*/v2/hooks' => Http::response(['mensagem' => 'CNPJ não habilitado'], 422)]);

        Livewire::test(ConfiguracaoClinica::class)->call('ativarAvisoNfse')
            ->assertSet('flashErro', 'A Focus NFe não aceitou o aviso automático: CNPJ não habilitado');
        $this->assertNull($this->clinica->fresh()->nfse_webhook_em);
    }

    public function test_aviso_com_token_certo_consulta_a_nota_e_com_token_errado_e_recusado(): void
    {
        Queue::fake();
        $this->clinica->update(['nfse_webhook_token' => 'segredo-da-focus', 'nfse_webhook_em' => now(), 'nfse_webhook_homologacao' => true]);
        $nota = $this->nota();

        $this->postJson('/api/webhook/nfse', ['ref' => $nota->referencia, 'status' => 'autorizado'], ['Authorization' => 'errado'])
            ->assertStatus(401);
        Queue::assertNothingPushed();

        $this->postJson('/api/webhook/nfse', ['ref' => 'ref-de-outro-sistema'], ['Authorization' => 'segredo-da-focus'])
            ->assertOk()->assertJson(['status' => 'ignorado']);

        $this->postJson('/api/webhook/nfse', ['ref' => $nota->referencia, 'status' => 'autorizado'], ['Authorization' => 'segredo-da-focus'])
            ->assertOk()->assertJson(['status' => 'ok']);
        Queue::assertPushed(ConsultarNotaFiscalJob::class, fn ($job) => $job->notaId === $nota->id);
    }

    // ─── Notas presas ────────────────────────────────────────────

    public function test_varredura_reenvia_consulta_e_desiste_das_notas_presas(): void
    {
        Queue::fake();
        $nuncaEnviada = $this->nota(['consultas' => 0]);
        $esquecida    = $this->nota(['consultas' => 12, 'ultima_consulta_em' => now()->subHour()]);
        $recente      = $this->nota(['consultas' => 3, 'ultima_consulta_em' => now()->subMinutes(5)]);
        $antiga       = $this->nota(['consultas' => 12]);
        NotaFiscal::whereKey([$nuncaEnviada->id, $esquecida->id, $recente->id])->update(['created_at' => now()->subMinutes(20)]);
        NotaFiscal::whereKey($antiga->id)->update(['created_at' => now()->subHours(49)]);
        $novinha = $this->nota(['consultas' => 0]); // acabou de ser criada: a fila ainda vai enviar

        $total = app(RevisarNotasProcessandoAction::class)->execute();

        $this->assertSame(['reenviadas' => 1, 'consultadas' => 1, 'expiradas' => 1], $total);
        Queue::assertPushed(EnviarNotaFiscalJob::class, fn ($job) => $job->notaId === $nuncaEnviada->id);
        Queue::assertPushed(ConsultarNotaFiscalJob::class, fn ($job) => $job->notaId === $esquecida->id);
        Queue::assertNotPushed(EnviarNotaFiscalJob::class, fn ($job) => $job->notaId === $novinha->id);
        $this->assertSame(StatusNotaFiscal::Erro, $antiga->fresh()->status);
        $this->assertStringContainsString('painel da Focus', $antiga->fresh()->mensagem_erro);
        $this->assertSame(StatusNotaFiscal::Processando, $recente->fresh()->status);
    }

    public function test_falha_definitiva_no_envio_marca_erro_em_vez_de_ficar_processando(): void
    {
        $nota = $this->nota(['consultas' => 0]);

        (new EnviarNotaFiscalJob($nota->id))->failed(new RuntimeException('Connection refused'));

        $this->assertSame(StatusNotaFiscal::Erro, $nota->fresh()->status);
        $this->assertStringContainsString('Não foi possível falar com a Focus NFe', $nota->fresh()->mensagem_erro);
    }

    // ─── CPF e endereço do tomador ───────────────────────────────

    public function test_documento_confere_digitos_de_cpf_e_cnpj(): void
    {
        $this->assertTrue(Documento::valido('123.456.789-09'));
        $this->assertFalse(Documento::valido('123.456.789-00'));
        $this->assertFalse(Documento::valido('111.111.111-11'));
        $this->assertTrue(Documento::valido('11.222.333/0001-81'));
        $this->assertFalse(Documento::valido('11.222.333/0001-80'));
    }

    public function test_cpf_com_digito_errado_nao_emite(): void
    {
        Livewire::test(NotaFiscalIndex::class)->call('abrirEmissao')->call('escolherReceita', $this->receita->id)
            ->set('tomadorCpf', '123.456.789-00')
            ->call('emitir')
            ->assertSet('flashErro', 'O CPF do tomador não é válido. Confira os números.');
        $this->assertSame(0, NotaFiscal::count());
    }

    public function test_cep_preenche_endereco_e_ele_vai_na_nota_e_fica_no_paciente(): void
    {
        Http::fake([
            'viacep.com.br/*'                           => Http::response(self::VIACEP),
            'homologacao.focusnfe.com.br/v2/nfse?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfse/*'     => Http::response(['status' => 'processando_autorizacao']),
        ]);

        Livewire::test(NotaFiscalIndex::class)->call('abrirEmissao')->call('escolherReceita', $this->receita->id)
            ->set('endCep', '71900100')
            ->assertSet('endCep', '71900-100')
            ->assertSet('endLogradouro', 'Rua 12')
            ->assertSet('endCidade', 'Brasília')
            ->assertSet('endCodigoMunicipio', '5300108')
            ->set('endNumero', '300')
            ->set('endComplemento', 'apto 101')
            ->call('emitir')
            ->assertHasNoErrors()
            ->assertSet('flashErro', null);

        $nota = NotaFiscal::sole();
        $this->assertSame('5300108', $nota->tomador_endereco['codigo_municipio']);
        $this->assertSame(now()->subDays(3)->toDateString(), $nota->data_competencia->toDateString());
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v2/nfse?ref=')
            && $r['tomador']['endereco'] === [
                'logradouro' => 'Rua 12', 'numero' => '300', 'complemento' => 'apto 101', 'bairro' => 'Águas Claras',
                'codigo_municipio' => '5300108', 'uf' => 'DF', 'cep' => '71900100',
            ]);

        // O paciente não tinha endereço: a próxima nota já vem preenchida
        $paciente = $this->paciente->fresh();
        $this->assertSame('71900100', $paciente->cep);
        $this->assertSame('300', $paciente->numero);
    }

    public function test_endereco_incompleto_nao_emite(): void
    {
        Livewire::test(NotaFiscalIndex::class)->call('abrirEmissao')->call('escolherReceita', $this->receita->id)
            ->set('endLogradouro', 'Rua 12')
            ->call('emitir')
            ->assertSet('flashErro', 'Complete o endereço do tomador (falta: CEP, número, bairro, UF, cidade pelo CEP) ou deixe todos os campos em branco.');
        $this->assertSame(0, NotaFiscal::count());
    }

    public function test_padrao_nacional_leva_endereco_e_competencia_do_lancamento(): void
    {
        $nota = $this->nota([
            'padrao' => PadraoNfse::Nacional, 'data_competencia' => now()->subDays(3)->toDateString(),
            'tomador_endereco' => ['cep' => '71900100', 'logradouro' => 'Rua 12', 'numero' => '300', 'bairro' => 'Águas Claras', 'uf' => 'DF', 'codigo_municipio' => '5300108'],
        ]);
        $this->clinica->update(['nfse_padrao' => PadraoNfse::Nacional, 'nfse_codigo_tributacao_nacional' => '060201']);

        $json = app(MontarNotaFocus::class)->montar($nota->fresh(), $this->clinica->fresh());

        $this->assertSame(now()->subDays(3)->toDateString(), $json['data_competencia']);
        $this->assertSame('5300108', $json['codigo_municipio_tomador']);
        $this->assertSame('71900100', $json['cep_tomador']);
        $this->assertSame('300', $json['numero_tomador']);
    }

    public function test_ficha_do_paciente_busca_cep_e_salva_endereco_separado(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(self::VIACEP)]);

        Livewire::test(PacienteIndex::class)
            ->call('abrirModalEditar', $this->paciente->id)
            ->set('endCep', '71900-100')
            ->assertSet('endBairro', 'Águas Claras')
            ->set('endNumero', '42')
            ->call('atualizar')
            ->assertHasNoErrors();

        $paciente = $this->paciente->fresh();
        $this->assertSame(['71900100', 'Rua 12', '42', 'Brasília', 'DF', '5300108'],
            [$paciente->cep, $paciente->logradouro, $paciente->numero, $paciente->cidade, $paciente->uf, $paciente->codigo_municipio]);
    }

    public function test_cep_inexistente_avisa(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => 'true'])]);

        Livewire::test(PacienteIndex::class)->call('abrirModalEditar', $this->paciente->id)
            ->set('endCep', '00000-000')
            ->assertSet('endAviso', 'Não encontramos esse CEP. Confira os números.')
            ->assertSet('endCodigoMunicipio', '');
    }
}
