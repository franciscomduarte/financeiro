<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusCampanha;
use App\Enums\StatusDestinatarioCampanha;
use App\Jobs\EnviarCampanhasJob;
use App\Livewire\CampanhaEditor;
use App\Livewire\CampanhaIndex;
use App\Models\Agendamento;
use App\Models\Campanha;
use App\Models\CampanhaDestinatario;
use App\Models\Paciente;
use App\Models\PacienteMensagem;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Campanhas de WhatsApp: escolha dos pacientes, ritmo de envio (intervalo, horário, limite) e controle. */
class CampanhasTest extends TestCase
{
    use RefreshDatabase;

    private bool $whatsappRecusa = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 12)->setTime(10, 0)); // segunda-feira
        $this->actingAs(User::factory()->create(['role' => 'admin', 'name' => 'Gestora']));
        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc', 'whatsapp_numero' => '(61) 98888-0000']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(fn () => $this->whatsappRecusa
            ? Http::response(['error' => 'not a whatsapp number'], 400)
            : Http::response(['key' => ['id' => 'MSG-' . Str::random(6)]], 201));
        Queue::fake([EnviarCampanhasJob::class]);
    }

    private function paciente(string $nome, ?string $telefone = '(61) 97777-1111', array $extra = []): Paciente
    {
        return Paciente::create(['nome' => $nome, 'telefone' => $telefone] + $extra);
    }

    private function campanha(array $pacientes, string $mensagem = "Olá, {primeiro_nome}! Promoção de outubro 💜"): Campanha
    {
        $c = Campanha::create(['nome' => 'Outubro', 'mensagem' => $mensagem, 'status' => StatusCampanha::Rascunho]);
        foreach ($pacientes as $p) {
            CampanhaDestinatario::create(['campanha_id' => $c->id, 'paciente_id' => $p->id, 'status' => StatusDestinatarioCampanha::Pendente]);
        }

        return $c;
    }

    /** Roda o próximo job da cadeia (como a fila faria) e devolve quantos jobs estavam na fila. */
    private function rodarCadeia(): void
    {
        $token = Cache::get(EnviarCampanhasJob::chaveCadeia($this->clinica->id));
        $this->assertNotNull($token, 'cadeia não está ativa');
        app()->call([new EnviarCampanhasJob($token), 'handle']);
    }

    private function mensagensEnviadas(): array
    {
        return collect(Http::recorded())->map(fn ($par) => $par[0]['textMessage']['text'] ?? $par[0]['caption'] ?? null)->filter()->values()->all();
    }

    public function test_escolhe_pacientes_com_filtros_e_inicia(): void
    {
        $ana = $this->paciente('Ana Paula', '(61) 97777-1111', ['data_nascimento' => '1990-10-05']);
        $this->paciente('Bia Souza', '(61) 96666-2222', ['data_nascimento' => '1985-03-10']);
        $this->paciente('Carla Sem Celular', null, ['data_nascimento' => '1992-10-20']);
        $dani = $this->paciente('Dani Lima', '(61) 95555-3333', ['data_nascimento' => '1995-10-30', 'aceita_whatsapp_marketing' => true]);

        Livewire::test(CampanhaIndex::class)->call('nova')->assertRedirect();
        $campanha = Campanha::sole();

        $tela = Livewire::test(CampanhaEditor::class, ['campanha' => $campanha->id])
            ->set('aniversarioMes', '10')
            ->assertSee('Ana Paula')->assertSee('Dani Lima')->assertDontSee('Bia Souza')->assertDontSee('Carla Sem Celular')
            ->call('marcarTodos')
            ->assertSee('2 pacientes marcados.')
            ->set('soAutorizados', true)
            ->assertDontSee('Ana Paula')
            ->set('soAutorizados', false)
            ->call('alternar', $ana->id) // desmarca a Ana
            ->set('mensagem', "Oi, {primeiro_nome}! Mês do seu aniversário tem desconto 🎉")
            ->call('salvar')
            ->assertSee('Oi, Maria! Mês do seu aniversário tem desconto');

        $this->assertSame([$dani->id], CampanhaDestinatario::pluck('paciente_id')->all());

        $tela->call('iniciar')->assertSee('Campanha iniciada');
        $campanha->refresh();
        $this->assertSame(StatusCampanha::Enviando, $campanha->status);
        $this->assertSame(1, $campanha->total);
        Queue::assertPushed(EnviarCampanhasJob::class, 1);

        // Depois de iniciada, a lista não muda
        $tela->call('alternar', $ana->id)->assertSee('já foi iniciada');
        $this->assertSame(1, CampanhaDestinatario::count());
    }

    public function test_envia_um_por_vez_com_intervalo_sorteado_e_conclui(): void
    {
        $ana = $this->paciente('Ana Paula', '(61) 97777-1111');
        $semCelular = $this->paciente('Bia Sem Número', '123');
        $campanha = $this->campanha([$ana, $semCelular]);
        app(\App\Actions\Campanhas\GerenciarCampanhaAction::class)->iniciar($campanha);

        $this->rodarCadeia();

        $this->assertSame(['Olá, Ana! Promoção de outubro 💜'], $this->mensagensEnviadas());
        Queue::assertPushed(EnviarCampanhasJob::class, function (EnviarCampanhasJob $job) {
            $espera = $job->delay?->diffInSeconds(now());

            return $espera !== null && abs($espera) >= 30 && abs($espera) <= 90;
        });
        $this->assertTrue(PacienteMensagem::where('paciente_id', $ana->id)->where('enviada', true)->exists()); // fica na conversa da ficha

        $this->rodarCadeia(); // Bia: sem celular válido → pulada

        $campanha->refresh();
        $this->assertSame(StatusCampanha::Concluida, $campanha->status);
        $this->assertSame([1, 1], [$campanha->enviados, $campanha->falhas]);
        $this->assertSame(StatusDestinatarioCampanha::Ignorado, CampanhaDestinatario::where('paciente_id', $semCelular->id)->sole()->status);
        $this->assertCount(1, $this->mensagensEnviadas());
    }

    public function test_respeita_horario_e_limite_diario(): void
    {
        config(['campanhas.limite_diario' => 2]);
        $pacientes = collect(range(1, 3))->map(fn ($i) => $this->paciente("Paciente {$i}", "(61) 9777{$i}-111{$i}"));
        $campanha = $this->campanha($pacientes->all());
        app(\App\Actions\Campanhas\GerenciarCampanhaAction::class)->iniciar($campanha);

        $this->rodarCadeia();
        $this->rodarCadeia();
        $this->rodarCadeia(); // limite de 2 por dia: o terceiro espera

        $this->assertCount(2, $this->mensagensEnviadas());
        $this->assertNull(Cache::get(EnviarCampanhasJob::chaveCadeia($this->clinica->id))); // cadeia parou

        // No dia seguinte, mas às 20h (fora do horário): não envia
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 13)->setTime(20, 0));
        EnviarCampanhasJob::iniciarCadeia();
        $this->rodarCadeia();
        $this->assertCount(2, $this->mensagensEnviadas());

        // Domingo também não
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 18)->setTime(10, 0));
        EnviarCampanhasJob::iniciarCadeia();
        $this->rodarCadeia();
        $this->assertCount(2, $this->mensagensEnviadas());

        // Segunda às 9h: termina
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 19)->setTime(9, 0));
        EnviarCampanhasJob::iniciarCadeia();
        $this->rodarCadeia();
        $this->assertCount(3, $this->mensagensEnviadas());
        $this->assertSame(StatusCampanha::Concluida, $campanha->fresh()->status);
    }

    public function test_falhas_seguidas_pausam_e_cancelar_tira_da_fila(): void
    {
        $pacientes = collect(range(1, 5))->map(fn ($i) => $this->paciente("Paciente {$i}", "(61) 9777{$i}-111{$i}"));
        $campanha = $this->campanha($pacientes->all());
        app(\App\Actions\Campanhas\GerenciarCampanhaAction::class)->iniciar($campanha);
        $this->whatsappRecusa = true;

        $this->rodarCadeia();
        $this->rodarCadeia();
        $this->rodarCadeia();

        $this->assertSame(StatusCampanha::Pausada, $campanha->fresh()->status);
        $this->assertSame(3, $campanha->fresh()->falhas);

        Livewire::test(CampanhaEditor::class, ['campanha' => $campanha->id])
            ->assertSee('Pausada')
            ->call('cancelar')->assertSee('Campanha cancelada');

        $this->assertSame(StatusCampanha::Cancelada, $campanha->fresh()->status);
        $this->assertSame(0, CampanhaDestinatario::where('status', 'pendente')->count());
    }

    public function test_imagem_vai_com_a_mensagem_como_legenda_e_teste_vai_para_a_gestao(): void
    {
        Storage::fake('local');
        $ana = $this->paciente('Ana Paula');
        $campanha = $this->campanha([$ana]);

        Livewire::test(CampanhaEditor::class, ['campanha' => $campanha->id])
            ->set('imagem', UploadedFile::fake()->image('promo.png', 600, 600))
            ->call('salvar')->assertHasNoErrors()
            ->call('enviarTeste')->assertSee('Teste enviado');

        $campanha->refresh();
        $this->assertNotNull($campanha->imagem_path);
        Storage::disk('local')->assertExists($campanha->imagem_path);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sendMedia') && $r['number'] === '5561988880000'
            && $r['mediatype'] === 'image' && $r['caption'] === 'Olá, Gestora! Promoção de outubro 💜');

        app(\App\Actions\Campanhas\GerenciarCampanhaAction::class)->iniciar($campanha);
        $this->rodarCadeia();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'sendMedia') && $r['number'] === '5561977771111' && $r['caption'] === 'Olá, Ana! Promoção de outubro 💜');
        $this->get(route('campanhas.imagem', $campanha->id))->assertOk();
    }

    public function test_filtro_de_procedimento_e_de_quem_sumiu(): void
    {
        $prof = new Profissional(['nome' => 'Lara', 'email' => 'lara@x.com', 'ativo' => true]);
        $prof->id = (string) Str::uuid();
        $prof->save();
        $botox = Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 30, 'valor' => 900, 'ativo' => true]);
        $ana  = $this->paciente('Ana Sumida');
        $bia  = $this->paciente('Bia Recente', '(61) 96666-2222');
        $cris = $this->paciente('Cris Nunca Fez', '(61) 95555-3333');
        foreach ([[$ana, '2026-03-10 10:00'], [$bia, '2026-10-01 10:00']] as [$p, $quando]) {
            Agendamento::create(['paciente_id' => $p->id, 'profissional_id' => $prof->id, 'procedimento_id' => $botox->id,
                'inicio_em' => $quando, 'fim_em' => date('Y-m-d H:i', strtotime($quando) + 1800), 'status' => 'realizado']);
        }
        $campanha = $this->campanha([]);

        Livewire::test(CampanhaEditor::class, ['campanha' => $campanha->id])
            ->set('procedimentoId', (string) $botox->id)
            ->assertSee('Ana Sumida')->assertSee('Bia Recente')->assertDontSee('Cris Nunca Fez')
            ->set('semVirDias', '90')
            ->assertSee('Ana Sumida')->assertDontSee('Bia Recente');
    }

    public function test_campanha_de_outra_clinica_nao_abre(): void
    {
        $campanha = $this->campanha([]);
        $outra = \App\Models\Clinica::create(['nome' => 'Outra', 'slug' => 'outra', 'status' => 'ativa']);
        app(ClinicaAtual::class)->definir($outra);

        Livewire::test(CampanhaEditor::class, ['campanha' => $campanha->id])->assertNotFound();
        $this->assertSame(0, Campanha::count()); // a outra clínica não enxerga a campanha
    }
}
