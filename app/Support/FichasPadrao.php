<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Fichas que toda clínica recebe prontas (e pode editar): Anamnese, Capilar, Corporal, Epilação,
 * Estética facial, Facial (injetáveis) e Ozonioterapia.
 */
final class FichasPadrao
{
    /** @return array<int, array{nome: string, descricao: string, campos: array<int, array<string, mixed>>}> */
    public static function todas(): array
    {
        $fototipo = ['I – pele muito clara, sempre queima', 'II – clara, queima com facilidade', 'III – morena clara, às vezes queima',
            'IV – morena, raramente queima', 'V – morena escura', 'VI – negra'];

        return [
            self::ficha('Anamnese', 'Histórico de saúde e queixa do paciente.', [
                self::rico('Queixa principal'),
                self::rico('Tratamentos anteriores'),
                self::multipla('Condições de saúde', ['Diabetes', 'Hipertensão', 'Problemas cardíacos', 'Distúrbio de coagulação', 'Doença autoimune',
                    'Epilepsia', 'Herpes recorrente', 'Tendência a queloide', 'Câncer (atual ou passado)', 'Nenhuma']),
                self::texto('Alergias'),
                self::texto('Medicamentos em uso'),
                self::simNao('Gestante ou amamentando'),
                self::simNao('Fumante'),
                self::simNao('Usa ácidos ou retinoides'),
                self::simNao('Exposição solar frequente'),
                self::rico('Observações'),
            ]),
            self::ficha('Capilar', 'Avaliação do couro cabeludo e dos fios.', [
                self::rico('Queixa'),
                self::escolha('Tipo de queda', ['Difusa', 'Frontal (entradas)', 'Coroa', 'Em placas (areata)', 'Sem queda']),
                self::escolha('Couro cabeludo', ['Normal', 'Oleoso', 'Seco', 'Com descamação', 'Sensível']),
                self::simNao('Histórico familiar de calvície'),
                self::multipla('Química nos fios', ['Coloração', 'Descoloração', 'Alisamento/progressiva', 'Permanente', 'Nenhuma']),
                self::rico('Conduta'),
            ]),
            self::ficha('Corporal', 'Medidas e avaliação corporal.', [
                self::rico('Queixa'),
                self::titulo('Medidas'),
                self::numero('Peso (kg)'),
                self::numero('Altura (cm)'),
                self::numero('Cintura (cm)'),
                self::numero('Abdômen (cm)'),
                self::numero('Quadril (cm)'),
                self::numero('Coxa (cm)'),
                self::titulo('Avaliação'),
                self::escolha('Celulite', ['Grau I', 'Grau II', 'Grau III', 'Grau IV', 'Sem celulite']),
                self::escolha('Flacidez', ['Leve', 'Moderada', 'Intensa', 'Sem flacidez']),
                self::multipla('Outras alterações', ['Gordura localizada', 'Estrias', 'Retenção de líquido', 'Fibrose']),
                self::rico('Conduta'),
            ]),
            self::ficha('Epilação', 'Avaliação para depilação a laser ou luz pulsada.', [
                self::escolha('Fototipo (Fitzpatrick)', $fototipo),
                self::multipla('Áreas', ['Axilas', 'Virilha', 'Pernas', 'Buço', 'Rosto', 'Costas', 'Peito', 'Braços', 'Glúteos']),
                self::escolha('Cor do pelo', ['Preto', 'Castanho', 'Ruivo', 'Loiro', 'Branco/grisalho']),
                self::escolha('Espessura do pelo', ['Fino', 'Médio', 'Grosso']),
                self::multipla('Métodos usados antes', ['Cera', 'Lâmina', 'Laser', 'Luz pulsada', 'Nenhum']),
                self::texto('Parâmetros do aparelho'),
                self::rico('Reação e observações'),
            ]),
            self::ficha('Estética Facial', 'Avaliação da pele do rosto.', [
                self::escolha('Biotipo cutâneo', ['Normal', 'Seca', 'Oleosa', 'Mista', 'Sensível']),
                self::escolha('Fototipo (Fitzpatrick)', $fototipo),
                self::multipla('Alterações', ['Acne', 'Manchas', 'Melasma', 'Rugas e linhas', 'Flacidez', 'Poros dilatados', 'Olheiras', 'Rosácea', 'Cicatrizes']),
                self::escolha('Grau de acne', ['Sem acne', 'Grau I (comedões)', 'Grau II (pápulas)', 'Grau III (pústulas)', 'Grau IV (nódulos)']),
                self::texto('Rotina de cuidados em casa'),
                self::rico('Procedimento realizado'),
                self::texto('Produtos utilizados'),
            ]),
            self::ficha('Facial', 'Harmonização e procedimentos injetáveis no rosto.', [
                self::rico('Avaliação'),
                self::multipla('Áreas tratadas', ['Testa', 'Glabela', 'Pés de galinha', 'Olheiras', 'Malar', 'Sulco nasogeniano', 'Lábios', 'Mento', 'Mandíbula', 'Pescoço']),
                self::rico('Conduta'),
                self::data('Retorno previsto'),
            ]),
            self::ficha('Ficha de Ozonioterapia', 'Registro da aplicação de ozônio.', [
                self::escolha('Via de aplicação', ['Subcutânea', 'Intramuscular', 'Intra-articular', 'Tópica (bag/óleo)', 'Insuflação']),
                self::numero('Concentração (µg/mL)'),
                self::numero('Volume (mL)'),
                self::texto('Áreas aplicadas'),
                self::simNao('Contraindicações conferidas (G6PD, hipertireoidismo, gestação)'),
                self::rico('Intercorrências e observações'),
            ]),
        ];
    }

    /** @param  array<int, array<string, mixed>>  $campos */
    private static function ficha(string $nome, string $descricao, array $campos): array
    {
        return ['nome' => $nome, 'descricao' => $descricao, 'campos' => $campos];
    }

    private static function campo(string $tipo, string $rotulo, array $opcoes = []): array
    {
        return ['id' => substr(md5($tipo . $rotulo), 0, 10), 'tipo' => $tipo, 'rotulo' => $rotulo, 'opcoes' => $opcoes];
    }

    private static function rico(string $r): array { return self::campo('texto_rico', $r); }
    private static function texto(string $r): array { return self::campo('texto', $r); }
    private static function numero(string $r): array { return self::campo('numero', $r); }
    private static function data(string $r): array { return self::campo('data', $r); }
    private static function simNao(string $r): array { return self::campo('sim_nao', $r); }
    private static function titulo(string $r): array { return self::campo('titulo', $r); }
    private static function escolha(string $r, array $o): array { return self::campo('escolha', $r, $o); }
    private static function multipla(string $r, array $o): array { return self::campo('multipla', $r, $o); }
}
