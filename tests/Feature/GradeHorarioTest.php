<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AgendamentoConfiguracaoIndex;
use App\Models\GradeHorario;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AgendamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GradeHorarioTest extends TestCase
{
    use RefreshDatabase;

    private Profissional $profissional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->profissional     = new Profissional(['nome' => 'Ana', 'email' => 'ana@teste.com', 'ativo' => true]);
        $this->profissional->id = (string) Str::uuid();
        $this->profissional->save();
    }

    public function test_intervalo_da_grade_nao_oferece_horarios(): void
    {
        GradeHorario::create([
            'profissional_id'  => $this->profissional->id,
            'dia_semana'       => 1,
            'hora_inicio'      => '09:00',
            'hora_fim'         => '14:00',
            'intervalo_inicio' => '12:00',
            'intervalo_fim'    => '13:00',
            'ativo'            => true,
        ]);

        // 05/10/2026 é segunda-feira
        $slots = app(AgendamentoService::class)->slotsDisponiveis($this->profissional->id, '2026-10-05', 60);

        $this->assertSame(['09:00', '09:30', '10:00', '10:30', '11:00', '13:00'], $slots);
    }

    public function test_salvar_grade_com_intervalo(): void
    {
        Livewire::test(AgendamentoConfiguracaoIndex::class)
            ->call('abrirModalGrade', $this->profissional->id)
            ->set('grade.1.tem_intervalo', true)
            ->set('grade.1.intervalo_inicio', '12:00')
            ->set('grade.1.intervalo_fim', '13:00')
            ->call('salvarGrade')
            ->assertHasNoErrors()
            ->assertSet('modalGrade', false);

        $segunda = GradeHorario::where('profissional_id', $this->profissional->id)->where('dia_semana', 1)->first();
        $terca   = GradeHorario::where('profissional_id', $this->profissional->id)->where('dia_semana', 2)->first();

        $this->assertSame(['12:00', '13:00'], $segunda->intervalo());
        $this->assertNull($terca->intervalo());
    }

    public function test_copiar_para_todos_aplica_apenas_nos_dias_ativos(): void
    {
        $componente = Livewire::test(AgendamentoConfiguracaoIndex::class)
            ->call('abrirModalGrade', $this->profissional->id)
            ->set('grade.1.hora_inicio', '08:00')
            ->set('grade.1.tem_intervalo', true)
            ->call('copiarGradeParaTodos', 1);

        $this->assertSame('08:00', $componente->get('grade.5.hora_inicio'));  // sexta, ativa
        $this->assertTrue($componente->get('grade.5.tem_intervalo'));
        $this->assertSame('09:00', $componente->get('grade.0.hora_inicio'));  // domingo, inativo
    }

    public function test_rejeita_fim_antes_do_inicio(): void
    {
        Livewire::test(AgendamentoConfiguracaoIndex::class)
            ->call('abrirModalGrade', $this->profissional->id)
            ->set('grade.1.hora_inicio', '18:00')
            ->set('grade.1.hora_fim', '09:00')
            ->call('salvarGrade')
            ->assertHasErrors(['grade.1.hora_fim' => 'after'])
            ->assertSet('modalGrade', true);

        $this->assertSame(0, GradeHorario::count());
    }

    public function test_rejeita_intervalo_fora_do_expediente(): void
    {
        Livewire::test(AgendamentoConfiguracaoIndex::class)
            ->call('abrirModalGrade', $this->profissional->id)
            ->set('grade.1.tem_intervalo', true)
            ->set('grade.1.intervalo_inicio', '17:30')
            ->set('grade.1.intervalo_fim', '19:00')
            ->call('salvarGrade')
            ->assertHasErrors(['grade.1.intervalo_fim' => 'before']);
    }

    public function test_dia_inativo_nao_e_validado(): void
    {
        Livewire::test(AgendamentoConfiguracaoIndex::class)
            ->call('abrirModalGrade', $this->profissional->id)
            ->set('grade.0.hora_inicio', '18:00')
            ->set('grade.0.hora_fim', '09:00')
            ->call('salvarGrade')
            ->assertHasNoErrors();
    }
}
