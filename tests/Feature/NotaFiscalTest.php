<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\NotaFiscal\CancelarNotaFiscalAction;
use App\Enums\StatusNotaFiscal;
use App\Jobs\ConsultarNotaFiscalJob;
use App\Livewire\ConfiguracaoClinica;
use App\Livewire\NotaFiscalIndex;
use App\Models\NotaFiscal;
use App\Models\Paciente;
use App\Models\Transacao;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** NFS-e pela Focus NFe (API simulada com Http::fake). */
class NotaFiscalTest extends TestCase
{
    use RefreshDatabase;

    private Transacao $receita;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $paciente = Paciente::create(['nome' => 'Maria Silva', 'cpf' => '123.456.789-09', 'email' => 'maria@exemplo.com'])->fresh();
        $this->receita = Transacao::factory()->create([
            'tipo' => 'entrada', 'status' => 'pago', 'valor_bruto' => 350, 'descricao' => 'Atendimento: Limpeza de pele',
            'paciente_id' => $paciente->id, 'cliente' => 'Maria Silva', 'data_competencia' => now()->toDateString(),
        ]);
    }

    private function configurarClinica(): void
    {
        $this->clinica->update([
            'cnpj' => '12.345.678/0001-90', 'nfse_token' => 'token-teste', 'nfse_homologacao' => true,
            'inscricao_municipal' => '0812345', 'codigo_municipio' => '3550308', 'nfse_item_lista_servico' => '06.02',
            'nfse_aliquota_iss' => 2, 'nfse_optante_simples' => true, 'nfse_discriminacao_padrao' => 'Serviços de estética.',
        ]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
    }

    private function emitir(): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(NotaFiscalIndex::class)
            ->call('abrirEmissao')
            ->call('escolherReceita', $this->receita->id)
            ->call('emitir');
    }

    public function test_sem_dados_fiscais_nao_emite(): void
    {
        $this->emitir()->assertSet('flashErro', 'Para emitir nota, complete em Dados da clínica › Nota fiscal: Token da Focus NFe, CNPJ, Inscrição municipal, Código IBGE do município, Item da lista de serviço, Alíquota do ISS.');
        $this->assertSame(0, NotaFiscal::count());
    }

    public function test_emite_e_fica_autorizada_com_numero_e_links(): void
    {
        $this->configurarClinica();
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfse?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfse/*'     => Http::response([
                'status' => 'autorizado', 'numero' => '1234', 'codigo_verificacao' => 'ABC123',
                'url' => 'https://nfse.prefeitura/nota/1234', 'caminho_xml_nota_fiscal' => '/arquivos/nota.xml',
            ]),
        ]);

        $tela = Livewire::test(NotaFiscalIndex::class)->call('abrirEmissao')->call('escolherReceita', $this->receita->id);
        $tela->assertSet('tomadorNome', 'Maria Silva')
            ->assertSet('tomadorCpf', '123.456.789-09')
            ->assertSet('discriminacao', "Serviços de estética.\nAtendimento: Limpeza de pele")
            ->call('emitir')
            ->assertHasNoErrors()
            ->assertSet('flashErro', null);

        $nota = NotaFiscal::sole();
        $this->assertSame(StatusNotaFiscal::Autorizada, $nota->status);
        $this->assertSame('1234', $nota->numero);
        $this->assertSame('https://homologacao.focusnfe.com.br/arquivos/nota.xml', $nota->url_xml);
        $this->assertTrue($nota->homologacao);

        Http::assertSent(function (Request $r): bool {
            if ($r->method() !== 'POST') {
                return false;
            }
            $this->assertSame('Basic ' . base64_encode('token-teste:'), $r->header('Authorization')[0]);
            $this->assertSame('12345678000190', $r['prestador']['cnpj']);
            $this->assertSame('3550308', $r['prestador']['codigo_municipio']);
            $this->assertSame('12345678909', $r['tomador']['cpf']);
            $this->assertSame('maria@exemplo.com', $r['tomador']['email']);
            $this->assertEquals(350, $r['servico']['valor_servicos']);
            $this->assertEquals(2, $r['servico']['aliquota']);
            $this->assertSame('06.02', $r['servico']['item_lista_servico']);

            return true;
        });

        // Não emite duas notas para a mesma receita
        $this->emitir()->assertSet('flashErro', 'Este lançamento já tem nota fiscal emitida ou em processamento.');
        $this->assertSame(1, NotaFiscal::count());
    }

    public function test_recusa_da_prefeitura_mostra_o_motivo_e_permite_emitir_de_novo(): void
    {
        $this->configurarClinica();
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfse?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfse/*'     => Http::response([
                'status' => 'erro_autorizacao',
                'erros'  => [['codigo' => 'E160', 'mensagem' => 'Inscrição municipal inválida', 'correcao' => 'Confira o cadastro']],
            ]),
        ]);

        $this->emitir();

        $nota = NotaFiscal::sole();
        $this->assertSame(StatusNotaFiscal::Erro, $nota->status);
        $this->assertSame('Inscrição municipal inválida (Confira o cadastro)', $nota->mensagem_erro);

        $this->emitir()->assertSet('flashErro', null);
        $this->assertSame(2, NotaFiscal::count());
    }

    public function test_erro_no_envio_marca_a_nota_com_erro(): void
    {
        $this->configurarClinica();
        Http::fake(['*' => Http::response(['codigo' => 'requisicao_invalida', 'mensagem' => 'CNPJ do prestador não habilitado'], 400)]);

        $this->emitir();

        $this->assertSame(StatusNotaFiscal::Erro, NotaFiscal::sole()->status);
        $this->assertSame('CNPJ do prestador não habilitado', NotaFiscal::sole()->mensagem_erro);
    }

    public function test_consulta_para_depois_de_muitas_tentativas(): void
    {
        $this->configurarClinica();
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfse?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfse/*'     => Http::response(['status' => 'processando_autorizacao']),
        ]);

        $this->emitir();

        $nota = NotaFiscal::sole();
        $this->assertSame(StatusNotaFiscal::Processando, $nota->status);
        $this->assertSame(ConsultarNotaFiscalJob::MAX_CONSULTAS, $nota->consultas);
    }

    public function test_nota_nao_encontrada_logo_apos_o_envio_continua_sendo_consultada(): void
    {
        $this->configurarClinica();
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfse?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfse/*'     => Http::sequence()
                ->push(['codigo' => 'nao_encontrado', 'mensagem' => 'Nota fiscal não encontrada'], 404)
                ->push(['status' => 'autorizado', 'numero' => '77']),
        ]);

        $this->emitir();

        $nota = NotaFiscal::sole();
        $this->assertSame(StatusNotaFiscal::Autorizada, $nota->status);
        $this->assertSame('77', $nota->numero);
    }

    public function test_nota_que_nunca_aparece_vira_erro_com_orientacao(): void
    {
        $this->configurarClinica();
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfse?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfse/*'     => Http::response(['codigo' => 'nao_encontrado'], 404),
        ]);

        $this->emitir();

        $nota = NotaFiscal::sole();
        $this->assertSame(StatusNotaFiscal::Erro, $nota->status);
        $this->assertSame(3, $nota->consultas);
        $this->assertStringContainsString('padrão', $nota->mensagem_erro);
    }

    public function test_padrao_nacional_usa_nfsen_e_o_leiaute_nacional(): void
    {
        $this->configurarClinica();
        $this->clinica->update(['nfse_padrao' => 'nacional', 'nfse_codigo_tributacao_nacional' => null]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());

        $this->emitir()->assertSet('flashErro', 'Para emitir nota, complete em Dados da clínica › Nota fiscal: Código de tributação nacional.');

        $this->clinica->update(['nfse_codigo_tributacao_nacional' => '060201', 'inscricao_municipal' => null, 'nfse_item_lista_servico' => null]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake([
            'homologacao.focusnfe.com.br/v2/nfsen?ref=*' => Http::response(['status' => 'processando_autorizacao'], 202),
            'homologacao.focusnfe.com.br/v2/nfsen/*'     => Http::response(['status' => 'autorizado', 'numero' => '5', 'url_danfse' => 'https://danfse/5']),
        ]);

        $this->emitir()->assertSet('flashErro', null);

        $nota = NotaFiscal::sole();
        $this->assertSame(StatusNotaFiscal::Autorizada, $nota->status);
        $this->assertSame('https://danfse/5', $nota->url);

        Http::assertSent(function (Request $r): bool {
            if ($r->method() !== 'POST') {
                return false;
            }
            $this->assertStringContainsString('/v2/nfsen?ref=nf-', $r->url());
            $this->assertSame('12345678000190', $r['cnpj_prestador']);
            $this->assertSame('3550308', $r['codigo_municipio_emissora']);
            $this->assertSame('060201', $r['codigo_tributacao_nacional_iss']);
            $this->assertSame('12345678909', $r['cpf_tomador']);
            $this->assertSame('Maria Silva', $r['razao_social_tomador']);
            $this->assertSame(3, $r['codigo_opcao_simples_nacional']);
            $this->assertEquals(350, $r['valor_servico']);
            $this->assertArrayNotHasKey('prestador', $r->data());

            return true;
        });
    }

    public function test_cancelamento_exige_justificativa_e_avisa_a_prefeitura(): void
    {
        $this->configurarClinica();
        $nota = NotaFiscal::create([
            'transacao_id' => $this->receita->id, 'referencia' => 'nf-teste', 'status' => StatusNotaFiscal::Autorizada,
            'homologacao' => true, 'valor' => 350, 'discriminacao' => 'x', 'tomador_nome' => 'Maria Silva', 'numero' => '1',
        ]);
        Http::fake(['homologacao.focusnfe.com.br/v2/nfse/nf-teste' => Http::response(['status' => 'cancelado'])]);

        try {
            app(CancelarNotaFiscalAction::class)->execute($nota->id, 'curto');
            $this->fail('Deveria exigir justificativa');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('15 caracteres', $e->getMessage());
        }

        Livewire::test(NotaFiscalIndex::class)
            ->call('abrirCancelamento', $nota->id)
            ->set('justificativa', 'Valor lançado errado, será emitida outra nota.')
            ->call('cancelar')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'Nota cancelada na prefeitura.');

        $this->assertSame(StatusNotaFiscal::Cancelada, $nota->fresh()->status);
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && $r['justificativa'] === 'Valor lançado errado, será emitida outra nota.');
    }

    public function test_despesa_nao_recebe_nota(): void
    {
        $this->configurarClinica();
        $despesa = Transacao::factory()->create(['tipo' => 'saida', 'status' => 'pago', 'valor_bruto' => 100]);

        Livewire::test(NotaFiscalIndex::class)
            ->call('abrirEmissao')
            ->call('escolherReceita', $despesa->id)
            ->assertSet('transacaoId', '')
            ->assertSet('flashErro', 'Lançamento não encontrado ou não é uma receita.');
    }

    public function test_token_fica_criptografado_e_nao_e_apagado_ao_salvar_em_branco(): void
    {
        Livewire::test(ConfiguracaoClinica::class)
            ->set('aba', 'nota_fiscal')
            ->set('nfseToken', 'segredo-123')
            ->set('codigoMunicipio', '5300108')
            ->set('nfseAliquotaIss', '2')
            ->call('salvarNotaFiscal')
            ->assertHasNoErrors()
            ->assertSet('nfseToken', '')
            ->call('salvarNotaFiscal');

        $this->assertSame('segredo-123', $this->clinica->fresh()->nfse_token);
        $this->assertNotSame('segredo-123', \DB::table('clinicas')->where('id', $this->clinica->id)->value('nfse_token'));
        $this->assertArrayNotHasKey('nfse_token', $this->clinica->fresh()->toArray());

        Livewire::test(ConfiguracaoClinica::class)
            ->set('aba', 'nota_fiscal')
            ->set('nfsePadrao', 'nacional')
            ->set('nfseCodigoNacional', '06.02.01')
            ->call('salvarNotaFiscal')
            ->assertHasErrors('nfseCodigoNacional')
            ->set('nfseCodigoNacional', '060201')
            ->call('salvarNotaFiscal')
            ->assertHasNoErrors()
            ->assertSee('Código de tributação nacional');
        $this->assertSame('060201', $this->clinica->fresh()->nfse_codigo_tributacao_nacional);

        Livewire::test(ConfiguracaoClinica::class)->set('codigoMunicipio', '123')->call('salvarNotaFiscal')->assertHasErrors('codigoMunicipio');
    }

    public function test_recepcao_nao_acessa_notas_fiscais(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'recepcao']))->get('/notas-fiscais')->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'financeiro']))->get('/notas-fiscais')->assertOk();
    }
}
