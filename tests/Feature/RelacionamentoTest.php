<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TipoContatoRelacionamento;
use App\Livewire\RelacionamentoIndex;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\PesquisaSatisfacao;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\RelacionamentoContato;
use App\Models\User;
use App\Services\ListasRelacionamentoService;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Relacionamento: retornos, aniversários, pacientes sumidos e pesquisa de satisfação. */
class RelacionamentoTest extends TestCase
{
    use RefreshDatabase;

    private Profissional $ana;
    private Procedimento $toxina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));

        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->ana       = new Profissional(['nome' => 'Ana Souza', 'email' => 'ana@x.com', 'ativo' => true]);
        $this->ana->id   = (string) Str::uuid();
        $this->ana->save();
        $this->toxina = Procedimento::create(['nome' => 'Toxina', 'duracao_minutos' => 30, 'valor' => 900, 'ativo' => true, 'retorno_dias' => 120]);
    }

    private function paciente(string $nome, array $extra = []): Paciente
    {
        return Paciente::create(['nome' => $nome, 'telefone' => '(61) 99999-0000', 'aceita_whatsapp_marketing' => true, ...$extra])->fresh();
    }

    private function atendimento(Paciente $p, string $quando, string $status = 'realizado'): Agendamento
    {
        return Agendamento::create([
            'paciente_id' => $p->id, 'profissional_id' => $this->ana->id, 'procedimento_id' => $this->toxina->id,
            'inicio_em' => $quando, 'fim_em' => date('Y-m-d H:i', strtotime($quando) + 1800), 'status' => $status,
        ]);
    }

    public function test_retorno_aparece_no_prazo_e_some_depois_do_lembrete(): void
    {
        $maria = $this->paciente('Maria Silva');
        $joao  = $this->paciente('João Lima');
        $bia   = $this->paciente('Bia Costa');
        $a     = $this->atendimento($maria, now()->subDays(118)->format('Y-m-d 10:00')); // vence em 2 dias
        $this->atendimento($joao, now()->subDays(40)->format('Y-m-d 10:00'));            // ainda longe
        $this->atendimento($bia, now()->subDays(121)->format('Y-m-d 10:00'));            // venceu, mas já marcou
        $this->atendimento($bia, now()->addDays(3)->format('Y-m-d 10:00'), 'agendado');

        $retornos = app(ListasRelacionamentoService::class)->retornos();
        $this->assertSame(['Maria Silva'], $retornos->pluck('nome')->all());

        Livewire::test(RelacionamentoIndex::class)
            ->call('enviar', 'retorno', $maria->id, $a->id, 'Toxina')
            ->assertSet('flashSucesso', 'Mensagem enviada por WhatsApp.');

        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'retorno de Toxina'));
        $this->assertTrue(app(ListasRelacionamentoService::class)->retornos()->isEmpty());

        // Não manda de novo
        Livewire::test(RelacionamentoIndex::class)
            ->call('enviar', 'retorno', $maria->id, $a->id, 'Toxina')
            ->assertSet('flashErro', 'Este contato já foi feito.');
    }

    public function test_sem_autorizacao_nao_envia_whatsapp_mas_pode_marcar_como_feito(): void
    {
        $p = $this->paciente('Carla Dias', ['aceita_whatsapp_marketing' => false, 'data_nascimento' => now()->subYears(30)->format('Y-m-d')]);
        $ano = (string) now()->year;

        $this->assertSame(['Carla Dias'], app(ListasRelacionamentoService::class)->aniversarios()->pluck('nome')->all());

        Livewire::test(RelacionamentoIndex::class)
            ->call('enviar', 'aniversario', $p->id, $ano, null)
            ->assertSet('flashErro', 'Este paciente não autorizou mensagens por WhatsApp. Fale com ele de outro jeito e marque como feito.')
            ->call('marcarFeito', 'aniversario', $p->id, $ano)
            ->assertSet('flashSucesso', 'Contato marcado como feito.');

        Http::assertNothingSent();
        $this->assertSame('manual', RelacionamentoContato::sole()->canal);
        $this->assertTrue(app(ListasRelacionamentoService::class)->aniversarios()->isEmpty());
    }

    public function test_sumidos_respeita_o_periodo_e_o_convite_recente(): void
    {
        $sumida = $this->paciente('Sumida');
        $this->atendimento($sumida, now()->subMonths(8)->format('Y-m-d 10:00'));
        $recente = $this->paciente('Recente');
        $this->atendimento($recente, now()->subMonths(2)->format('Y-m-d 10:00'));

        $listas = app(ListasRelacionamentoService::class);
        $this->assertSame(['Sumida'], $listas->sumidos(6)->pluck('nome')->all());
        $this->assertCount(0, $listas->sumidos(12));
        $this->assertSame(['Recente', 'Sumida'], $listas->sumidos(1)->pluck('nome')->all()); // quem sumiu há menos tempo primeiro

        RelacionamentoContato::create(['paciente_id' => $sumida->id, 'tipo' => TipoContatoRelacionamento::Sumido, 'referencia' => now()->format('Y-m'), 'canal' => 'manual']);
        $this->assertCount(0, $listas->sumidos(6));
    }

    public function test_pesquisa_enviada_e_respondida_pela_pagina_publica(): void
    {
        $p = $this->paciente('Maria Silva');
        $a = $this->atendimento($p, now()->subDay()->format('Y-m-d 10:00'));

        Livewire::test(RelacionamentoIndex::class, ['aba' => 'pesquisas'])
            ->call('enviarPesquisa', $a->id)
            ->assertSet('flashSucesso', 'Pesquisa enviada por WhatsApp.');

        $pesquisa = PesquisaSatisfacao::sole();
        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', $pesquisa->link()));
        $this->assertTrue(app(ListasRelacionamentoService::class)->semPesquisa()->isEmpty());

        // Página pública, sem login
        auth()->logout();
        $this->get('/avaliacao/' . $pesquisa->token)->assertOk()->assertSee('Como foi seu atendimento?');
        $this->post('/avaliacao/' . $pesquisa->token, ['nota' => 11])->assertSessionHasErrors('nota');
        $this->post('/avaliacao/' . $pesquisa->token, ['nota' => 9, 'comentario' => 'Adorei!'])->assertRedirect();
        $this->get('/avaliacao/' . $pesquisa->token)->assertSee('Obrigado pela resposta!');
        $this->post('/avaliacao/' . $pesquisa->token, ['nota' => 2])->assertSessionHasErrors('nota');

        $this->assertSame(9, $pesquisa->fresh()->nota);
        $this->get('/avaliacao/' . str_repeat('x', 48))->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $r = app(ListasRelacionamentoService::class)->resultadoPesquisas();
        $this->assertSame(100, $r['nps']);
        $this->assertSame(1, $r['respostas']);
    }

    public function test_pesquisa_responde_mesmo_com_clinica_em_somente_leitura(): void
    {
        $a = $this->atendimento($this->paciente('Maria Silva'), now()->subDay()->format('Y-m-d 10:00'));
        $pesquisa = PesquisaSatisfacao::create([
            'paciente_id' => $a->paciente_id, 'agendamento_id' => $a->id, 'token' => Str::random(48), 'enviada_em' => now(),
        ]);
        $this->clinica->update(['status' => 'teste', 'teste_ate' => now()->subDays(3)]);

        auth()->logout();
        $this->post('/avaliacao/' . $pesquisa->token, ['nota' => 8])->assertRedirect();
        $this->assertSame(8, $pesquisa->fresh()->nota);
    }

    public function test_tela_abre_para_recepcao_e_nao_para_financeiro(): void
    {
        $this->get('/relacionamento')->assertOk()->assertSee('Relacionamento');
        $this->actingAs(User::factory()->create(['role' => 'financeiro']))->get('/relacionamento')->assertRedirect();
    }
}
