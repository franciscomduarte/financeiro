<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Agenda\EnviarAgendaProfissionaisAction;
use App\Mail\AgendaProfissionalMail;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Véspera às 19h: cada profissional recebe por e-mail a agenda do dia seguinte. */
class AgendaProfissionaisTest extends TestCase
{
    use RefreshDatabase;

    private Procedimento $limpeza;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 10, 7)->setTime(19, 0));
        Http::fake();
        Mail::fake();
        $this->limpeza = Procedimento::create(['nome' => 'Limpeza de pele', 'duracao_minutos' => 60, 'valor' => 200, 'ativo' => true]);
    }

    private function profissional(string $nome, ?string $email, bool $ativo = true): Profissional
    {
        $p = new Profissional(['nome' => $nome, 'email' => $email, 'ativo' => $ativo]);
        $p->id = (string) Str::uuid();
        $p->save();

        return $p;
    }

    private function agendar(Profissional $prof, Paciente $paciente, string $inicio, array $extra = []): Agendamento
    {
        return Agendamento::create($extra + [
            'paciente_id' => $paciente->id, 'profissional_id' => $prof->id, 'procedimento_id' => $this->limpeza->id,
            'inicio_em' => $inicio, 'fim_em' => date('Y-m-d H:i', strtotime($inicio) + 3600), 'status' => 'agendado',
        ]);
    }

    public function test_cada_profissional_recebe_a_agenda_de_amanha(): void
    {
        $lara  = $this->profissional('Lara Cristian', 'lara@clinica.com');
        $bruno = $this->profissional('Bruno Lima', 'bruno@clinica.com');
        $inativa = $this->profissional('Carla Inativa', 'carla@clinica.com', ativo: false);
        $ana  = Paciente::create(['nome' => 'Ana Paula', 'telefone' => '(61) 97777-2222']);
        $bia  = Paciente::create(['nome' => 'Bia Nova', 'telefone' => '(61) 96666-1111']);

        $this->agendar($lara, $ana, '2026-09-20 10:00', ['status' => 'realizado']); // Ana já é paciente da casa
        $this->agendar($lara, $bia, '2026-10-08 14:00', ['observacoes' => 'Pele sensível']);
        $this->agendar($lara, $ana, '2026-10-08 09:00', ['status' => 'confirmado', 'confirmacao_origem' => 'paciente', 'confirmado_em' => now()]);
        $this->agendar($lara, $ana, '2026-10-08 16:00', ['status' => 'cancelado']);
        $this->agendar($lara, $bia, '2026-10-09 09:00'); // depois de amanhã
        $this->agendar($inativa, $bia, '2026-10-08 11:00');

        $resultado = app(EnviarAgendaProfissionaisAction::class)->execute();

        $this->assertSame(['profissionais' => 1, 'enviados' => 1], $resultado);
        Mail::assertSent(AgendaProfissionalMail::class, 1);
        Mail::assertSent(AgendaProfissionalMail::class, function (AgendaProfissionalMail $m) {
            $this->assertTrue($m->hasTo('lara@clinica.com'));
            $this->assertSame(['09:00', '14:00'], array_column($m->itens, 'inicio'));
            $this->assertSame(['Confirmado pelo paciente', 'Aguardando confirmação'], array_column($m->itens, 'confirmacao'));
            $this->assertSame([false, true], array_column($m->itens, 'primeira_vez'));
            $this->assertSame('Pele sensível', $m->itens[1]['observacoes']);
            $this->assertSame('🗓️ Sua agenda de amanhã (08/10) — 2 atendimentos', $m->envelope()->subject);

            $html = $m->render();
            $this->assertStringContainsString('Bia Nova', $html);
            $this->assertStringContainsString('Primeira vez na clínica', $html);
            $this->assertStringContainsString('📝 Pele sensível', $html);

            return true;
        });
        Mail::assertNotSent(AgendaProfissionalMail::class, fn ($m) => $m->hasTo('bruno@clinica.com') || $m->hasTo('carla@clinica.com')); // sem atendimentos amanhã; inativa

        // Nova execução (retentativa do job) não reenvia
        app(EnviarAgendaProfissionaisAction::class)->execute();
        Mail::assertSent(AgendaProfissionalMail::class, 1);
    }

    public function test_sem_atendimentos_amanha_nao_envia_nada(): void
    {
        $this->profissional('Lara Cristian', 'lara@clinica.com');

        $this->assertSame(['profissionais' => 0, 'enviados' => 0], app(EnviarAgendaProfissionaisAction::class)->execute());
        Mail::assertNothingSent();
    }
}
