<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateTransacaoAction;
use App\Livewire\ConfiguracaoClinica;
use App\Mail\AgendamentoCriadoMail;
use App\Models\Agendamento;
use App\Models\Clinica;
use App\Models\Cobranca;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AsaasService;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** Multiclínica — fase 2: configurações e integrações por clínica. */
class ConfiguracaoClinicaTest extends TestCase
{
    use RefreshDatabase;

    private Clinica $outra;

    protected function setUp(): void
    {
        parent::setUp();
        config(['evolution.url' => 'https://evo.exemplo.com']);

        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc', 'asaas_api_key' => 'asaas-lc', 'asaas_sandbox' => false]);
        $this->outra = $this->novaClinica('Clínica Bem Estar');
        $this->outra->update([
            'slogan' => 'cuidando de você', 'telefone' => '(61) 3333-4444',
            'evolution_instance' => 'bem-estar', 'evolution_api_key' => 'chave-be',
            'asaas_api_key' => 'asaas-be', 'asaas_sandbox' => true, 'aliquota_imposto' => 10,
        ]);
    }

    private function naOutra(callable $callback): mixed
    {
        return app(ClinicaAtual::class)->executarComo($this->outra->fresh(), $callback);
    }

    // ─── Integrações por clínica ──────────────────────────────────

    public function test_whatsapp_usa_a_instancia_e_a_chave_da_clinica_ativa(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        app(WhatsAppService::class)->enviarTexto('5561999990000', 'oi LC');
        $this->naOutra(fn () => app(WhatsAppService::class)->enviarTexto('5561999990000', 'oi BE'));

        Http::assertSent(fn ($r) => $r->url() === 'https://evo.exemplo.com/message/sendText/lc' && $r->header('apikey')[0] === 'chave-lc');
        Http::assertSent(fn ($r) => $r->url() === 'https://evo.exemplo.com/message/sendText/bem-estar' && $r->header('apikey')[0] === 'chave-be');
    }

    public function test_whatsapp_sem_instancia_nao_envia(): void
    {
        Http::fake();
        $this->clinica->update(['evolution_instance' => null]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());

        $this->assertFalse(app(WhatsAppService::class)->enviarTexto('5561999990000', 'oi'));
        Http::assertNothingSent();
    }

    public function test_mensagem_de_whatsapp_leva_o_nome_da_clinica(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $agendamento = $this->naOutra(fn () => $this->agendamento());

        $this->naOutra(fn () => app(WhatsAppService::class)->enviarConfirmacaoAgendamento($agendamento));

        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'], 'Clínica Bem Estar')
            && ! str_contains($r['textMessage']['text'], 'LC Estética'));
    }

    public function test_asaas_usa_a_conta_e_o_ambiente_da_clinica(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);

        app(AsaasService::class)->testarConexao();
        $this->naOutra(fn () => app(AsaasService::class)->testarConexao());

        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://api.asaas.com/') && $r->header('access_token')[0] === 'asaas-lc');
        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://sandbox.asaas.com/') && $r->header('access_token')[0] === 'asaas-be');
    }

    public function test_asaas_sem_chave_falha_com_mensagem_clara(): void
    {
        $this->clinica->update(['asaas_api_key' => null]);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não tem a chave do Asaas');
        app(AsaasService::class)->testarConexao();
    }

    public function test_webhook_do_asaas_valida_o_token_da_clinica_dona_da_cobranca(): void
    {
        $this->outra->update(['asaas_webhook_token' => 'token-be']);
        $cobranca = $this->naOutra(function () {
            $p = Paciente::create(['nome' => 'Paciente']);

            return Cobranca::create(['paciente_id' => $p->id, 'asaas_id' => 'pay_9', 'valor' => 50,
                'vencimento' => '2026-10-10', 'mes_referencia' => '2026-10', 'status' => 'PENDING']);
        });
        $payload = ['event' => 'PAYMENT_RECEIVED', 'payment' => ['id' => 'pay_9']];

        $this->postJson('/api/webhook/asaas', $payload, ['asaas-access-token' => 'token-errado'])->assertStatus(401);
        $this->assertSame('PENDING', $this->naOutra(fn () => Cobranca::find($cobranca->id)->status));

        $this->postJson('/api/webhook/asaas', $payload, ['asaas-access-token' => 'token-be'])->assertOk();
        $this->assertSame('RECEIVED', $this->naOutra(fn () => Cobranca::find($cobranca->id)->status));
    }

    // ─── Imposto, segredos e identidade ───────────────────────────

    public function test_imposto_usa_a_aliquota_da_clinica(): void
    {
        $dados = ['tipo' => 'entrada', 'fase' => 'operacao', 'categoria' => 'Massagem', 'descricao' => 'X',
            'valor_bruto' => 200, 'forma_pagamento' => 'pix', 'data_competencia' => '2026-10-01'];

        $lc = app(CreateTransacaoAction::class)->execute($dados);
        $be = $this->naOutra(fn () => app(CreateTransacaoAction::class)->execute($dados));

        $this->assertSame('12.00', $lc->imposto_estimado); // 6%
        $this->assertSame('20.00', $be->imposto_estimado); // 10%
    }

    public function test_chaves_ficam_criptografadas_e_fora_do_json(): void
    {
        $bruto = DB::table('clinicas')->where('id', $this->outra->id)->value('asaas_api_key');

        $this->assertNotSame('asaas-be', $bruto);
        $this->assertSame('asaas-be', $this->outra->fresh()->asaas_api_key);
        $this->assertArrayNotHasKey('asaas_api_key', $this->outra->fresh()->toArray());
    }

    public function test_email_ao_paciente_usa_a_identidade_da_clinica(): void
    {
        $html = $this->naOutra(fn () => (new AgendamentoCriadoMail($this->agendamento()))->render());
        $assunto = $this->naOutra(fn () => (new AgendamentoCriadoMail($this->agendamento()))->envelope()->subject);

        $this->assertStringContainsString('Clínica Bem Estar — cuidando de você', $html);
        $this->assertStringContainsString('(61) 3333-4444', $html);
        $this->assertStringNotContainsString('LC Estética', $html);
        $this->assertStringContainsString('Clínica Bem Estar', $assunto);
    }

    // ─── Tela de configurações ────────────────────────────────────

    public function test_so_admin_acessa_as_configuracoes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin/clinica')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/clinica')->assertOk()->assertSee('Configurações da clínica');
    }

    public function test_chave_em_branco_mantem_a_atual_e_nunca_volta_para_a_tela(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(ConfiguracaoClinica::class)
            ->assertSet('asaasApiKey', '')
            ->assertDontSee('asaas-lc')
            ->set('evolutionInstance', 'lc-nova')
            ->call('salvarIntegracoes')
            ->assertHasNoErrors();

        $this->assertSame('asaas-lc', $this->clinica->fresh()->asaas_api_key);
        $this->assertSame('lc-nova', $this->clinica->fresh()->evolution_instance);

        Livewire::test(ConfiguracaoClinica::class)
            ->set('asaasApiKey', 'asaas-novo')
            ->call('salvarIntegracoes')
            ->assertSet('asaasApiKey', '');

        $this->assertSame('asaas-novo', $this->clinica->fresh()->asaas_api_key);
    }

    public function test_salvar_dados_logo_e_aliquota(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(ConfiguracaoClinica::class)
            ->set('nome', 'LC Estética & Saúde')
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
            ->call('salvarDados')
            ->assertHasNoErrors()
            ->set('aliquotaImposto', '8,5')
            ->call('salvarFinanceiro')
            ->assertHasNoErrors();

        $c = $this->clinica->fresh();
        $this->assertSame('LC Estética & Saúde', $c->nome);
        $this->assertSame('8.50', $c->aliquota_imposto);
        $this->assertStringStartsWith("clinicas/{$c->id}/logo/", $c->logo_path);
        Storage::disk('public')->assertExists($c->logo_path);
    }

    public function test_logo_svg_e_recusado(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(ConfiguracaoClinica::class)
            ->set('logo', UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'))
            ->call('salvarDados')
            ->assertHasErrors(['logo']);
    }

    public function test_logo_vazio_ou_invalido_e_recusado(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(ConfiguracaoClinica::class)
            ->set('logo', UploadedFile::fake()->create('logo.png', 0, 'image/png'))
            ->call('salvarDados')
            ->assertHasErrors(['logo']);
    }

    private function agendamento(): Agendamento
    {
        $profissional     = new Profissional(['nome' => 'Ana', 'email' => Str::random(6) . '@x.com', 'ativo' => true]);
        $profissional->id = (string) Str::uuid();
        $profissional->save();

        return Agendamento::create([
            'paciente_id'     => Paciente::create(['nome' => 'Maria', 'telefone' => '(61) 99999-1111'])->id,
            'profissional_id' => $profissional->id,
            'procedimento_id' => Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 60, 'valor' => 800, 'ativo' => true])->id,
            'inicio_em'       => '2026-10-20 10:00',
            'fim_em'          => '2026-10-20 11:00',
            'status'          => 'agendado',
        ])->load(['paciente', 'profissional', 'procedimento']);
    }
}
