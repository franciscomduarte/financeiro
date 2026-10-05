<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Clinica;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/** Menu lateral do sistema: grupos de navegação, troca de clínica e usuário logado. */
class MenuLateral extends Component
{
    /** Ícones (heroicons outline 24px). */
    private const ICONES = [
        'inicio'       => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'agenda'       => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'pacientes'    => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'cobrancas'    => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
        'lancamentos'  => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
        'relatorios'   => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'taxas'        => 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
        'estoque'      => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        'produtos'     => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'movimentacoes' => 'M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5',
        'fornecedores' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'contratos'    => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        'contas'       => 'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
        'fiscal'       => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z',
        'documentos'   => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
        'horarios'     => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'clinica'      => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'orcamentos'   => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
        'pacotes'      => 'M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        'comissoes'    => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
        'usuarios'     => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    ];

    private const ICONES_EXTRA = [
        'conta'      => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z',
        'plataforma' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
    ];

    /** Módulo exigido por cada rota do menu (itens sem acesso não aparecem). */
    private const MODULO_DA_ROTA = [
        'dashboard'             => 'inicio',
        'agenda.index'          => 'agenda',
        'pacientes.index'       => 'pacientes',
        'cobrancas.index'       => 'cobrancas',
        'orcamentos.index'      => 'cobrancas',
        'pacotes.index'         => 'cobrancas',
        'comissoes.index'       => 'relatorios',
        'transacoes.index'      => 'lancamentos',
        'web.relatorio'         => 'relatorios',
        'taxas-cartao.index'    => 'taxas',
        'estoque.index'         => 'estoque',
        'estoque.produtos'      => 'estoque',
        'estoque.movimentacoes' => 'estoque',
        'web.fornecedores'      => 'administrativo',
        'web.contratos'         => 'administrativo',
        'web.contas-consumo'    => 'administrativo',
        'web.obrigacoes-fiscais' => 'administrativo',
        'web.documentos'        => 'administrativo',
        'agenda.configuracao'   => 'configuracao_agenda',
        'admin.clinica'         => 'dados_clinica',
        'admin.usuarios'        => 'usuarios',
        'prontuario.modelos'    => 'dados_clinicos',
    ];

    private ?\App\Models\User $usuario = null;

    public ?Clinica $clinica;
    public bool $suporte;
    /** @var array<int, array{rotulo: string, rota: string, ativo: bool, icone: string}> */
    public array $menuUsuario;
    /** @var Collection<int, Clinica> */
    public Collection $outrasClinicas;
    /** @var array<int, array{titulo: ?string, chave: string, ativo: bool, itens: array<int, array{rotulo: string, rota: string, ativo: bool, icone: string}>}> */
    public array $grupos;

    public function __construct(ClinicaAtual $clinicaAtual)
    {
        $this->clinica = $clinicaAtual->get();
        $user          = auth()->user();

        $this->outrasClinicas = $user
            ? $user->clinicas()->select(['clinicas.id', 'clinicas.nome'])->where('clinicas.id', '!=', $this->clinica?->id)->limit(20)->get()
            : collect();

        $this->usuario = $user;
        $this->suporte = $clinicaAtual->emSuporte();
        $temClinica    = $this->clinica !== null;
        if ($this->suporte) {
            $this->outrasClinicas = collect(); // no suporte não se troca de clínica pelo menu
        }

        // Navegação do dia a dia (grupos recolhíveis); configurações e conta ficam no menu do usuário
        $this->grupos = ! $temClinica ? [] : array_values(array_filter(array_map(fn (array $g) => $g['itens'] ? $g : null, [
            $this->grupo(null, [
                ['Início', 'dashboard', 'dashboard', 'inicio'],
            ]),
            $this->grupo('Atendimento', [
                ['Agenda', 'agenda.index', 'agenda.index', 'agenda'],
                ['Pacientes', 'pacientes.index', 'pacientes.*', 'pacientes'],
                ['Orçamentos', 'orcamentos.index', 'orcamentos.*', 'orcamentos'],
                ['Pacotes', 'pacotes.index', 'pacotes.*', 'pacotes'],
                ['Cobranças', 'cobrancas.index', 'cobrancas.*', 'cobrancas'],
            ]),
            $this->grupo('Financeiro', [
                ['Lançamentos', 'transacoes.index', 'transacoes.*', 'lancamentos'],
                ['Comissões', 'comissoes.index', 'comissoes.*', 'comissoes'],
                ['Relatórios', 'web.relatorio', 'web.relatorio', 'relatorios'],
                ['Taxas de cartão', 'taxas-cartao.index', 'taxas-cartao.*', 'taxas'],
            ]),
            $this->grupo('Estoque', [
                ['Visão geral', 'estoque.index', 'estoque.index', 'estoque'],
                ['Produtos', 'estoque.produtos', 'estoque.produtos', 'produtos'],
                ['Movimentações', 'estoque.movimentacoes', 'estoque.movimentacoes', 'movimentacoes'],
            ]),
            $this->grupo('Administrativo', [
                ['Fornecedores', 'web.fornecedores', 'web.fornecedores', 'fornecedores'],
                ['Contratos', 'web.contratos', 'web.contratos', 'contratos'],
                ['Contas de consumo', 'web.contas-consumo', 'web.contas-consumo', 'contas'],
                ['Obrigações fiscais', 'web.obrigacoes-fiscais', 'web.obrigacoes-fiscais', 'fiscal'],
                ['Documentos', 'web.documentos', 'web.documentos', 'documentos'],
            ]),
        ])));

        $this->menuUsuario = $this->itens(array_filter([
            $temClinica && ! $this->suporte ? ['Minha conta', 'minha-conta', 'minha-conta', 'conta'] : null,
            $temClinica ? ['Modelos de termos e orientações', 'prontuario.modelos', 'prontuario.modelos', 'documentos'] : null,
            $temClinica ? ['Profissionais e horários', 'agenda.configuracao', 'agenda.configuracao', 'horarios'] : null,
            $temClinica ? ['Dados da clínica', 'admin.clinica', 'admin.clinica', 'clinica'] : null,
            $temClinica ? ['Usuários', 'admin.usuarios', 'admin.usuarios', 'usuarios'] : null,
            $user?->is_super_admin ? ['Painel da plataforma', 'plataforma.index', 'plataforma.*', 'plataforma'] : null,
        ]));

        // Dono da plataforma sem clínica: o painel é a única navegação
        if (! $temClinica && $user?->is_super_admin) {
            $this->grupos = [$this->grupo(null, [['Clínicas', 'plataforma.index', 'plataforma.*', 'plataforma']])];
        }
    }

    /** @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $itens */
    private function grupo(?string $titulo, array $itens): array
    {
        $itens = $this->itens($itens);

        return [
            'titulo' => $titulo,
            'chave'  => $titulo ? \Illuminate\Support\Str::slug($titulo) : 'principal',
            'ativo'  => in_array(true, array_column($itens, 'ativo'), true),
            'itens'  => $itens,
        ];
    }

    /** @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $itens */
    private function itens(array $itens): array
    {
        // Só o que o perfil acessa (rotas fora do mapa, como a do painel da plataforma, não são filtradas)
        $itens = array_filter($itens, function (array $i): bool {
            $modulo = self::MODULO_DA_ROTA[$i[1]] ?? null;

            return $modulo === null || (bool) $this->usuario?->pode(\App\Enums\Modulo::from($modulo));
        });

        return array_map(fn (array $i) => [
            'rotulo' => $i[0],
            'rota'   => $i[1],
            'ativo'  => request()->routeIs($i[2]),
            'icone'  => self::ICONES[$i[3]] ?? self::ICONES_EXTRA[$i[3]],
        ], array_values($itens));
    }

    public function render(): View
    {
        return view('components.menu-lateral');
    }
}
