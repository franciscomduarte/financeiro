<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendamentoConfiguracaoIndex;
use App\Livewire\AgendamentoIndex;
use App\Models\BloqueioAgenda;
use App\Models\GradeHorario;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AgendamentoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Bloquear horários direto da Agenda (e o mesmo formulário em Profissionais e horários). */
class AgendaBloqueioTest extends TestCase
{
    use RefreshDatabase;

    private function profissional(string $nome, ?int $userId = null): Profissional
    {
        $p = new Profissional(['nome' => $nome, 'email' => Str::slug($nome) . '@teste.com', 'ativo' => true]);
        $p->id = (string) Str::uuid();
        $p->user_id = $userId;
        $p->save();
        foreach (range(0, 6) as $dia) {
            GradeHorario::create(['profissional_id' => $p->id, 'dia_semana' => $dia, 'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true]);
        }

        return $p;
    }

    public function test_bloquear_pela_agenda_a_partir_do_horario_clicado(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));
        $ana  = $this->profissional('Ana');
        $dia  = CarbonImmutable::today()->addDays(3)->toDateString();

        Livewire::test(AgendamentoIndex::class)
            ->assertSee('Bloquear horário')
            ->call('novoNoHorario', $dia, '10:00')
            ->set('criarProfissionalId', $ana->id)
            ->assertSee('Bloquear este horário em vez de agendar')
            ->call('bloquearNoHorario')
            ->assertSet('modalCriar', false)
            ->assertSet('modalBloqueio', true)
            ->assertSet('bloqDiaInteiro', false)
            ->assertSet('bloqDataInicio', $dia)
            ->assertSet('bloqHoraInicio', '10:00')
            ->assertSet('bloqHoraFim', '11:00')
            ->assertSet('bloqProfissionalId', $ana->id)
            ->set('bloqMotivo', 'Reunião')
            ->call('salvarBloqueio')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'Bloqueio criado.')
            ->assertSet('modalBloqueio', false);

        $bloqueio = BloqueioAgenda::sole();
        $this->assertSame("{$dia} 10:00", $bloqueio->inicio_em->format('Y-m-d H:i'));
        $this->assertSame('Reunião', $bloqueio->motivo);

        // O horário bloqueado some dos horários livres
        $livres = app(AgendamentoService::class)->slotsDisponiveis($ana->id, $dia, 30);
        $this->assertNotContains('10:00', $livres);
        $this->assertNotContains('10:30', $livres);
        $this->assertContains('11:00', $livres);
    }

    public function test_botao_do_topo_bloqueia_dia_inteiro_de_todos_e_remove_pelo_calendario(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));
        $this->profissional('Ana');
        $this->profissional('Bia');
        $dia = CarbonImmutable::today()->addDays(5)->toDateString();

        $tela = Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalNovoBloqueio', null, null, '')
            ->assertSet('bloqProfissionalId', 'todos')
            ->assertSet('bloqDiaInteiro', true)
            ->set('bloqDataInicio', $dia)->set('bloqDataFim', $dia)->set('bloqMotivo', 'Feriado')
            ->call('salvarBloqueio')
            ->assertHasNoErrors();
        $this->assertSame(2, BloqueioAgenda::count());

        $tela->call('removerBloqueio', BloqueioAgenda::first()->id)->assertSet('flashSucesso', 'Bloqueio excluído.');
        $this->assertSame(0, BloqueioAgenda::count());
    }

    public function test_profissional_bloqueia_e_remove_so_a_propria_agenda(): void
    {
        $user = User::factory()->create(['role' => 'profissional']);
        $this->actingAs($user);
        $propria = $this->profissional('Ana', $user->id);
        $outra   = $this->profissional('Bia');
        $dia     = CarbonImmutable::today()->addDays(2)->toDateString();

        // Mesmo tentando escolher "todos" ou outra profissional, o bloqueio fica na própria agenda
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalNovoBloqueio', $dia, '14:00', $outra->id)
            ->assertSet('bloqProfissionalId', $propria->id)
            ->assertDontSee('Todos os profissionais')
            ->set('bloqProfissionalId', 'todos')
            ->call('salvarBloqueio')
            ->assertHasNoErrors();
        $this->assertSame([$propria->id], BloqueioAgenda::pluck('profissional_id')->all());

        // Não remove bloqueio de outra profissional
        $daOutra = BloqueioAgenda::create(['profissional_id' => $outra->id, 'inicio_em' => "{$dia} 08:00", 'fim_em' => "{$dia} 09:00", 'dia_inteiro' => false]);
        Livewire::test(AgendamentoIndex::class)
            ->call('removerBloqueio', $daOutra->id)
            ->assertSet('flashErro', 'Você só pode excluir bloqueios da sua própria agenda. Bloqueios de todos os profissionais são excluídos pela administração.');
        $this->assertTrue(BloqueioAgenda::whereKey($daOutra->id)->exists());
    }

    public function test_tela_de_configuracao_continua_bloqueando_e_avisa_agendamentos_no_periodo(): void
    {
        $this->actingAs(User::factory()->create());
        $ana = $this->profissional('Ana');
        $dia = CarbonImmutable::today()->addDays(4)->toDateString();
        \App\Models\Agendamento::create([
            'paciente_id'     => \App\Models\Paciente::create(['nome' => 'Carla', 'telefone' => '(61) 99999-0000'])->id,
            'profissional_id' => $ana->id,
            'procedimento_id' => \App\Models\Procedimento::create(['nome' => 'Botox', 'duracao_minutos' => 30, 'valor' => 900, 'ativo' => true])->id,
            'inicio_em'       => "{$dia} 09:00",
            'fim_em'          => "{$dia} 09:30",
            'status'          => 'agendado',
        ]);

        Livewire::test(AgendamentoConfiguracaoIndex::class)
            ->set('aba', 'bloqueios')
            ->call('abrirModalNovoBloqueio')
            ->assertSee('Todos os profissionais')
            ->set('bloqProfissionalId', $ana->id)->set('bloqDataInicio', $dia)->set('bloqDataFim', $dia)
            ->call('salvarBloqueio')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'Bloqueio criado. 1 agendamento continua nesse período: reagende ou cancele.')
            ->assertSee('Carla');
    }
}
