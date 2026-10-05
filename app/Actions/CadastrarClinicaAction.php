<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUsuario;
use App\Enums\StatusClinica;
use App\Mail\NovaClinicaCadastradaMail;
use App\Models\Clinica;
use App\Models\User;
use App\Services\VerificacaoEmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Autocadastro "Assine já": cria a clínica em teste grátis, o primeiro administrador e as
 * configurações iniciais. Depois de gravar, envia a confirmação de e-mail e avisa o dono da plataforma.
 */
class CadastrarClinicaAction
{
    public function __construct(
        private readonly CriarPadroesClinicaAction $padroes,
        private readonly VerificacaoEmailService $verificacao,
    ) {}

    /**
     * @param  array{clinica: string, nome: string, email: string, celular: string, password: string}  $dados
     * @return array{clinica: Clinica, user: User}
     */
    public function execute(array $dados): array
    {
        $resultado = DB::transaction(function () use ($dados): array {
            $clinica = Clinica::create([
                'nome'          => $dados['clinica'],
                'slug'          => $this->slugUnico($dados['clinica']),
                'status'        => StatusClinica::Teste,
                // O dia do cadastro conta como o 1º dia de teste
                'teste_ate'     => today()->addDays(max(1, (int) config('clinica.dias_teste')) - 1),
                'telefone'      => $dados['celular'],
                'email_contato' => $dados['email'],
            ]);

            $user = User::create([
                'name'     => $dados['nome'],
                'email'    => $dados['email'],
                'password' => $dados['password'],
                'role'     => RoleUsuario::Admin->value,
                'active'   => true,
            ]);
            $user->clinicas()->attach($clinica->id, ['papel' => RoleUsuario::Admin->value]);

            $this->padroes->execute($clinica);

            \App\Models\ClinicaEvento::create([
                'clinica_id' => $clinica->id,
                'user_id'    => $user->id,
                'acao'       => \App\Enums\AcaoClinicaEvento::Cadastro,
                'detalhes'   => ['teste_ate' => $clinica->teste_ate->toDateString()],
            ]);

            return ['clinica' => $clinica, 'user' => $user];
        });

        Log::info('[Cadastro] nova clínica em teste', [
            'tenant_id' => $resultado['clinica']->id,
            'user_id'   => $resultado['user']->id,
            'teste_ate' => $resultado['clinica']->teste_ate->toDateString(),
        ]);

        $this->verificacao->enviar($resultado['user']);

        if ($destino = config('plataforma.email')) {
            Mail::to($destino)->queue(new NovaClinicaCadastradaMail(
                $resultado['clinica'],
                $resultado['user'],
                $dados['celular'],
            ));
        }

        return $resultado;
    }

    private function slugUnico(string $nome): string
    {
        $base = Str::limit(Str::slug($nome) ?: 'clinica', 60, '');

        do {
            $slug = $base . '-' . Str::lower(Str::random(5));
        } while (Clinica::where('slug', $slug)->exists());

        return $slug;
    }
}
