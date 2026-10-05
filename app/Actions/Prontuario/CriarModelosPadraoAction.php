<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Enums\TipoModeloProntuario;
use App\Models\ProntuarioModelo;
use Illuminate\Support\Facades\DB;

/** Modelos prontos para a clínica começar: um termo de consentimento e orientações pós-procedimento. */
class CriarModelosPadraoAction
{
    public const TERMO = <<<'TXT'
        Eu, {paciente}, CPF {cpf}, nascido(a) em {data_nascimento}, declaro que fui informado(a) pela equipe da {clinica}, de forma clara e em linguagem acessível, sobre o procedimento {procedimento}, a ser realizado por {profissional}.

        Recebi explicações sobre:
        • como o procedimento é feito, o resultado esperado e o tempo de recuperação;
        • os riscos e possíveis efeitos, como vermelhidão, inchaço, hematomas, sensibilidade e, mais raramente, reações alérgicas ou infecção;
        • os cuidados que devo seguir antes e depois, e que o resultado pode variar de pessoa para pessoa;
        • as alternativas existentes, inclusive a de não fazer o procedimento.

        Informei todas as minhas condições de saúde, medicamentos em uso, alergias e procedimentos anteriores. Tive a oportunidade de tirar minhas dúvidas e sei que posso revogar este consentimento a qualquer momento antes do procedimento.

        Autorizo também o registro de fotos antes e depois, para uso exclusivo no meu prontuário, sem divulgação sem a minha autorização por escrito.

        {clinica}, {data}.
        TXT;

    public const ORIENTACAO = <<<'TXT'
        Olá, {paciente}! Seguem os cuidados após o seu procedimento ({procedimento}):

        • Nas primeiras 24 horas, evite tocar, massagear ou coçar a região tratada.
        • Não faça exercícios intensos, sauna ou banho muito quente nas primeiras 24 a 48 horas.
        • Evite exposição ao sol e use protetor solar FPS 50 todos os dias.
        • Não use maquiagem ou ácidos na região até ser liberado(a) pela profissional.
        • Beba bastante água.
        • Inchaço e vermelhidão leves são esperados e melhoram em poucos dias.

        Se tiver dor forte, febre, bolhas ou qualquer sinal diferente, entre em contato com a clínica.

        {profissional} — {clinica}
        TXT;

    public function execute(): void
    {
        DB::transaction(function (): void {
            if (! ProntuarioModelo::query()->where('tipo', TipoModeloProntuario::Termo)->exists()) {
                ProntuarioModelo::create([
                    'tipo' => TipoModeloProntuario::Termo, 'titulo' => 'Termo de consentimento para procedimento estético', 'conteudo' => self::TERMO,
                ]);
            }

            if (! ProntuarioModelo::query()->where('tipo', TipoModeloProntuario::Orientacao)->exists()) {
                ProntuarioModelo::create([
                    'tipo' => TipoModeloProntuario::Orientacao, 'titulo' => 'Cuidados após o procedimento', 'conteudo' => self::ORIENTACAO,
                ]);
            }
        });
    }
}
