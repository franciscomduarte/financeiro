<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Treinamento inicial do assistente da LC Estética e Saúde Integrativa (clínica "lc-estetica") e liga o atendimento.
 * Não sobrescreve o que a clínica já cadastrou: item com o mesmo título é mantido; configuração só ganha o que falta.
 * Tudo pode ser ajustado depois em Leads › Assistente.
 */
return new class extends Migration
{
    private const ITENS = [
        'Endereço e como chegar' => "Edifício Connect Towers – QS 1, Bloco D, Sala 1617 (16º andar), Águas Claras/DF.\nFica ao lado do Taguatinga Shopping.",
        'Horário de funcionamento' => "Segunda a sábado, das 8h às 19h.\nO atendimento é com hora marcada.",
        'Formas de pagamento' => "Cartão de crédito em até 10x, cartão de débito, dinheiro ou Pix.",
        'Avaliação' => "A avaliação custa R\$ 150,00 e é feita com a profissional Lara Cristian.\nNela a profissional entende o caso da pessoa e indica o tratamento, o número de sessões e o valor final.\nToda indicação de tratamento é feita na avaliação.",
        'Preços' => "Os preços dos procedimentos são sempre informados como \"a partir de\".\nO valor final depende de cada caso e é definido na avaliação.",
        'Tratamentos da clínica' => "A LC Estética e Saúde Integrativa trabalha com estética avançada e saúde integrativa, entre eles: ozonioterapia, toxina botulínica (botox), fios de PDO, terapia capilar, terapia neural, skinboosters e protocolos de drenagem.\nOs detalhes de cada tratamento, para quem é indicado e quantas sessões são necessárias são definidos na avaliação.",
        'Site da clínica' => "https://www.lcsaudeintegrativa.com.br",
    ];

    private const INSTRUCOES = "Sempre informe os preços como \"a partir de R\$ ...\" e explique que o valor final é definido na avaliação.\n"
        . "Quando a pessoa quiser saber mais sobre um tratamento, convide para a avaliação (R\$ 150,00 com a profissional Lara Cristian).\n"
        . "Não ofereça desconto nem condição especial.";

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return; // dados reais da clínica; os testes montam o próprio cenário
        }

        $tenantId = DB::table('clinicas')->where('slug', 'lc-estetica')->value('id');
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $agora = now();

            foreach (self::ITENS as $titulo => $conteudo) {
                $existe = DB::table('assistente_conhecimentos')->where('tenant_id', $tenantId)->where('titulo', $titulo)->exists();
                if (! $existe) {
                    DB::table('assistente_conhecimentos')->insert([
                        'id' => (string) Str::uuid7(), 'tenant_id' => $tenantId, 'titulo' => $titulo, 'conteudo' => $conteudo,
                        'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                }
            }

            // Procedimento usado para agendar a avaliação (o existente, ou um novo de R$ 150)
            $avaliacaoId = DB::table('procedimentos')->where('tenant_id', $tenantId)->where('nome', 'ilike', 'avalia%')->orderByDesc('ativo')->value('id')
                ?? DB::table('procedimentos')->insertGetId([
                    'tenant_id' => $tenantId, 'nome' => 'Avaliação', 'duracao_minutos' => 60, 'valor' => 150,
                    'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
                ]);
            $laraId = DB::table('profissionais')->where('tenant_id', $tenantId)->where('nome', 'ilike', 'lara%')->orderByDesc('ativo')->value('id');

            $config = DB::table('assistente_configuracoes')->where('tenant_id', $tenantId)->first();
            $dados = [
                'ativo'                     => true,
                'informar_precos'           => true,
                'pode_agendar'              => $laraId !== null,
                'procedimento_avaliacao_id' => $config->procedimento_avaliacao_id ?? $avaliacaoId,
                'profissional_id'           => $config->profissional_id ?? $laraId,
                'instrucoes'                => filled($config->instrucoes ?? null) ? $config->instrucoes : self::INSTRUCOES,
                'resposta_sem_informacao'   => filled($config->resposta_sem_informacao ?? null) ? $config->resposta_sem_informacao : 'Vou confirmar com a equipe e já te respondo, tá bom? 😊',
                'nome'                      => filled($config->nome ?? null) && $config->nome !== 'Assistente' ? $config->nome : 'Assistente da LC Estética',
                'updated_at'                => $agora,
            ];

            if ($config === null) {
                DB::table('assistente_configuracoes')->insert($dados + [
                    'id' => (string) Str::uuid7(), 'tenant_id' => $tenantId, 'limite_respostas_mes' => 500, 'created_at' => $agora,
                ]);
            } else {
                DB::table('assistente_configuracoes')->where('id', $config->id)->update($dados);
            }
        });
    }

    public function down(): void
    {
        $tenantId = DB::table('clinicas')->where('slug', 'lc-estetica')->value('id');
        if ($tenantId === null) {
            return;
        }

        // Desliga o atendimento e tira só os itens criados aqui (se ninguém mudou o texto)
        DB::table('assistente_configuracoes')->where('tenant_id', $tenantId)->update(['ativo' => false, 'updated_at' => now()]);
        foreach (self::ITENS as $titulo => $conteudo) {
            DB::table('assistente_conhecimentos')->where('tenant_id', $tenantId)->where('titulo', $titulo)->where('conteudo', $conteudo)->delete();
        }
    }
};
