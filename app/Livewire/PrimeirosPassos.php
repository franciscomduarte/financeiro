<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\TaxaCartao;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Lista de primeiros passos no Início para administradores de clínicas novas.
 * Some quando tudo está feito ou quando a clínica dispensa.
 */
class PrimeirosPassos extends Component
{
    public function dispensar(ClinicaAtual $clinicaAtual): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $clinica = $clinicaAtual->get();
        $clinica->forceFill(['primeiros_passos_dispensado_em' => now()])->save();

        Log::info('[PrimeirosPassos] lista dispensada', ['tenant_id' => $clinica->id, 'user_id' => auth()->id()]);
    }

    /** @return array<int, array{titulo: string, texto: string, feito: bool, rota: string, acao: string}> */
    public function passos(ClinicaAtual $clinicaAtual): array
    {
        $clinica = $clinicaAtual->get();

        return [
            [
                'titulo' => 'Coloque o logo e os dados da clínica',
                'texto'  => 'Eles aparecem nos e-mails e mensagens enviados aos pacientes.',
                'feito'  => filled($clinica->logo_path) || (filled($clinica->telefone) && filled($clinica->endereco)),
                'rota'   => route('admin.clinica'),
                'acao'   => 'Completar dados',
            ],
            [
                'titulo' => 'Cadastre os profissionais',
                'texto'  => 'Cada profissional tem sua agenda, horários e cor no calendário.',
                'feito'  => Profissional::query()->exists(),
                'rota'   => route('agenda.configuracao'),
                'acao'   => 'Cadastrar profissional',
            ],
            [
                'titulo' => 'Cadastre os procedimentos',
                'texto'  => 'Com duração e valor, para agendar e lançar a receita automaticamente.',
                'feito'  => Procedimento::query()->exists(),
                'rota'   => route('agenda.configuracao'),
                'acao'   => 'Cadastrar procedimento',
            ],
            [
                'titulo' => 'Ajuste as taxas da maquininha',
                'texto'  => 'Assim o valor líquido de cada pagamento sai certinho.',
                'feito'  => TaxaCartao::query()->where('percentual', '>', 0)->exists(),
                'rota'   => route('taxas-cartao.index'),
                'acao'   => 'Ajustar taxas',
            ],
            [
                'titulo' => 'Conecte o WhatsApp',
                'texto'  => 'Para enviar confirmações e lembretes de consulta automaticamente.',
                'feito'  => $clinica->whatsappConfigurado(),
                'rota'   => route('admin.clinica', ['aba' => 'integracoes']),
                'acao'   => 'Conectar',
            ],
            [
                'titulo' => 'Cadastre o primeiro paciente',
                'texto'  => 'Ou importe aos poucos, conforme os atendimentos acontecem.',
                'feito'  => Paciente::query()->exists(),
                'rota'   => route('pacientes.index'),
                'acao'   => 'Cadastrar paciente',
            ],
            [
                'titulo' => 'Faça o primeiro agendamento',
                'texto'  => 'O paciente recebe a confirmação por e-mail e WhatsApp.',
                'feito'  => Agendamento::query()->exists(),
                'rota'   => route('agenda.index'),
                'acao'   => 'Abrir agenda',
            ],
        ];
    }

    public function render(ClinicaAtual $clinicaAtual): View
    {
        $clinica = $clinicaAtual->get();
        $visivel = $clinica !== null
            && $clinica->primeiros_passos_dispensado_em === null
            && (bool) auth()->user()?->isAdmin();

        $passos    = $visivel ? $this->passos($clinicaAtual) : [];
        $feitos    = count(array_filter($passos, fn (array $p) => $p['feito']));
        $mostrar   = $visivel && $feitos < count($passos);

        return view('livewire.primeiros-passos', [
            'mostrar' => $mostrar,
            'passos'  => $passos,
            'feitos'  => $feitos,
        ]);
    }
}
