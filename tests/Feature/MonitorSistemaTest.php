<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\BatimentoFilaJob;
use App\Mail\AlertaSistemaMail;
use App\Models\User;
use App\Services\SaudeSistemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Monitor do sistema: fila parada, tarefas com falha e erros repetidos avisam o dono da plataforma. */
class MonitorSistemaTest extends TestCase
{
    use RefreshDatabase;

    private User $dono;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->dono = User::factory()->create(['role' => 'admin', 'email' => 'dono@exemplo.com']);
        $this->dono->forceFill(['is_super_admin' => true])->save();
        (new BatimentoFilaJob())->handle(); // fila viva
    }

    private function falhaDeJob(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\EnviarNotificacaoJob']),
            'exception' => "RuntimeException: Conexão recusada\n#0 trace", 'failed_at' => now(),
        ]);
    }

    public function test_tudo_certo_nao_avisa(): void
    {
        $this->artisan('sistema:monitorar')->expectsOutput('Tudo certo.')->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_falha_de_job_avisa_uma_vez_e_depois_avisa_que_normalizou(): void
    {
        config(['services.monitor.emails' => 'suporte@exemplo.com, invalido']);
        $this->falhaDeJob();

        $this->artisan('sistema:monitorar')->assertSuccessful();
        Mail::assertSent(AlertaSistemaMail::class, fn (AlertaSistemaMail $m) => $m->hasTo('dono@exemplo.com') && $m->hasTo('suporte@exemplo.com')
            && str_contains($m->problemas['jobs_falharam'], 'EnviarNotificacaoJob: RuntimeException: Conexão recusada'));

        $this->artisan('sistema:monitorar')->assertSuccessful(); // mesmo problema: não repete na mesma hora
        Mail::assertSentCount(1);

        $this->travel(61)->minutes();
        (new BatimentoFilaJob())->handle();
        $this->artisan('sistema:monitorar')->assertSuccessful(); // a falha saiu da janela de 1h
        Mail::assertSentCount(2);
        Mail::assertSent(AlertaSistemaMail::class, fn (AlertaSistemaMail $m) => $m->problemas === [] && $m->normalizados === ['tarefas com falha']);
    }

    public function test_fila_parada_e_erros_repetidos(): void
    {
        Cache::put(SaudeSistemaService::CHAVE_INICIO, now()->subHour()->getTimestamp());
        $this->travel(20)->minutes(); // sem batimento há 20 minutos

        foreach (range(1, SaudeSistemaService::LIMITE_ERROS_HORA) as $i) {
            Log::error("Falha ao falar com o Asaas #{$i}");
        }

        $problemas = app(SaudeSistemaService::class)->problemas();
        $this->assertStringContainsString('não processa tarefas há 20 minutos', $problemas['fila_parada']);
        $this->assertStringContainsString('Falha ao falar com o Asaas #20', $problemas['erros_repetidos']);

        (new BatimentoFilaJob())->handle();
        $this->assertArrayNotHasKey('fila_parada', app(SaudeSistemaService::class)->problemas());
    }

    public function test_painel_da_plataforma_mostra_a_saude(): void
    {
        $this->falhaDeJob();
        $this->actingAs($this->dono)->get('/plataforma')
            ->assertOk()->assertSee('Saúde do sistema')->assertSee('Falhas (24h)')->assertSee('EnviarNotificacaoJob');
    }
}
