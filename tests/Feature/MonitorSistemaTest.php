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
use Illuminate\Support\Facades\Storage;
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

    // ─── Backup ───────────────────────────────────────────────────

    private function ligarBackup(): void
    {
        Storage::fake('backups');
        config(['filesystems.disks.backups.bucket' => 'meu-bucket', 'backup.backup.password' => 'segredo', 'backup.backup.name' => 'financeiro']);
    }

    public function test_backup_desligado_nao_avisa_e_aparece_como_nao_configurado(): void
    {
        config(['filesystems.disks.backups.bucket' => null]);

        $this->assertFalse(app(SaudeSistemaService::class)->metricas()['backup']['configurado']);
        $this->artisan('sistema:monitorar')->expectsOutput('Tudo certo.')->assertSuccessful();
    }

    public function test_backup_que_falha_avisa_e_o_seguinte_bem_sucedido_normaliza(): void
    {
        $this->ligarBackup();
        Storage::disk('backups')->put('financeiro/' . now()->format('Y-m-d-H-i-s') . '.zip', 'zip');

        event(new \Spatie\Backup\Events\BackupHasFailed(new \Exception('pg_dump: conexão recusada'), 'backups', 'financeiro'));
        $this->artisan('sistema:monitorar')->assertSuccessful();
        Mail::assertSent(AlertaSistemaMail::class, fn (AlertaSistemaMail $m) => str_contains($m->problemas['backup_falhou'] ?? '', 'pg_dump: conexão recusada'));

        event(new \Spatie\Backup\Events\BackupWasSuccessful('backups', 'financeiro'));
        $this->assertSame([], app(SaudeSistemaService::class)->problemas());
        $this->assertSame(0, app(SaudeSistemaService::class)->metricas()['backup']['ultimo_horas']);
    }

    public function test_backup_atrasado_avisa(): void
    {
        $this->ligarBackup();
        Storage::disk('backups')->put('financeiro/' . now()->subHours(30)->format('Y-m-d-H-i-s') . '.zip', 'zip');

        $problemas = app(SaudeSistemaService::class)->problemas();

        $this->assertStringContainsString('há 30 horas', $problemas['backup_atrasado'] ?? '');
    }

    public function test_sem_nenhum_backup_so_avisa_depois_do_primeiro_dia(): void
    {
        $this->ligarBackup();

        $this->assertArrayNotHasKey('backup_atrasado', app(SaudeSistemaService::class)->problemas());

        Cache::put(SaudeSistemaService::CHAVE_INICIO, now()->subHours(27)->getTimestamp());
        $this->assertSame('Nenhum backup encontrado no armazenamento.', app(SaudeSistemaService::class)->problemas()['backup_atrasado'] ?? null);
    }

    public function test_agenda_o_backup_diario_so_quando_configurado(): void
    {
        $eventos = fn () => collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'backup:run'));

        config(['filesystems.disks.backups.bucket' => null]);
        $this->assertFalse($eventos()->first()->filtersPass($this->app));

        $this->ligarBackup();
        $this->assertTrue($eventos()->first()->filtersPass($this->app));
        $this->assertSame('0 2 * * *', $eventos()->first()->expression);
    }
}
