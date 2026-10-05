<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Importação de pacientes por CSV (comando pacientes:importar). Dados fictícios. */
class ImportarPacientesTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $conteudo): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'pac') . '.csv';
        file_put_contents($arquivo, $conteudo);

        return $arquivo;
    }

    private const CABECALHO = "Nome,Tipo,Tags,Telefone,E-mail,Ativo,Data de nascimento,Idade,Sexo,Estado civil,Profissão,Endereço,CPF,RG,CNPJ,Cor,Origem,Convênio\n";

    public function test_importa_normaliza_e_nao_duplica(): void
    {
        $arquivo = $this->csv(self::CABECALHO
            . "Maria Teste,Paciente,,+55 (61) 99999-1111,MARIA@EXEMPLO.COM,Sim,06/11/1972,53,Feminino,Solteiro,professora,\"Rua 1, 10, Centro, Brasília/DF, 00000-000\",111.222.333-44,,,,Instagram,\n"
            . "JOSÉ DA SILVA,Paciente,,+55 (61) 98888-2222,email-invalido,Não,,,Masculino,Casado,,,,,,,Indicação,\n"
            . "Sem Telefone,Paciente,,,,Sim,31/02/1990,,,,,,123,,,,,\n"
            . ",Paciente,,,,Sim,,,,,,,,,,,,\n"
            . "Fornecedor X,Fornecedor,,,,Sim,,,,,,,,,,,,\n");

        $this->artisan('pacientes:importar', ['arquivo' => $arquivo, '--clinica' => 'lc-estetica', '--simular' => true])->assertSuccessful();
        $this->assertSame(0, Paciente::count());

        $this->artisan('pacientes:importar', ['arquivo' => $arquivo, '--clinica' => 'lc-estetica'])
            ->expectsOutputToContain('Fornecedor X não é paciente')
            ->assertSuccessful();

        $this->assertSame(3, Paciente::count());

        $maria = Paciente::where('cpf', '111.222.333-44')->sole();
        $this->assertSame('Maria Teste', $maria->nome);
        $this->assertSame('(61) 99999-1111', $maria->telefone);
        $this->assertSame('maria@exemplo.com', $maria->email);
        $this->assertSame('1972-11-06', $maria->data_nascimento->toDateString());
        $this->assertSame('Solteiro(a)', $maria->estado_civil);
        $this->assertSame('Professora', $maria->profissao);
        $this->assertSame('Rua 1, 10, Centro, Brasília/DF', $maria->endereco);
        $this->assertSame('Instagram', $maria->origem);
        $this->assertFalse($maria->aceita_whatsapp_marketing); // sem consentimento na planilha

        $jose = Paciente::where('nome', 'José da Silva')->sole();
        $this->assertSame('inativo', $jose->status->value);
        $this->assertNull($jose->email);

        $semTel = Paciente::where('nome', 'Sem Telefone')->sole();
        $this->assertNull($semTel->data_nascimento); // data inválida
        $this->assertNull($semTel->cpf);              // CPF incompleto

        // Rodar de novo não duplica; só completa o que estava vazio, sem sobrescrever
        $maria->update(['profissao' => 'Dentista', 'endereco' => null]);
        $this->artisan('pacientes:importar', ['arquivo' => $arquivo, '--clinica' => 'lc-estetica'])->assertSuccessful();

        $this->assertSame(3, Paciente::count());
        $this->assertSame('Dentista', $maria->fresh()->profissao);
        $this->assertSame('Rua 1, 10, Centro, Brasília/DF', $maria->fresh()->endereco);
    }

    public function test_exige_clinica_e_coluna_nome(): void
    {
        $arquivo = $this->csv("Telefone\n61999999999\n");

        $this->artisan('pacientes:importar', ['arquivo' => $arquivo])->assertFailed();
        $this->artisan('pacientes:importar', ['arquivo' => $arquivo, '--clinica' => 'lc-estetica'])
            ->expectsOutputToContain('A planilha precisa ter a coluna "Nome".')
            ->assertFailed();
    }
}
