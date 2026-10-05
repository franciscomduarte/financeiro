<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/** Dá ou tira o acesso ao painel da plataforma. Só pelo servidor: não existe tela para isso. */
class SuperAdminCommand extends Command
{
    protected $signature = 'plataforma:super-admin {email : E-mail do usuário} {--remover : Tira o acesso em vez de dar}';

    protected $description = 'Dá (ou tira, com --remover) o acesso de dono da plataforma a um usuário';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();
        if ($user === null) {
            $this->error('Nenhum usuário com esse e-mail.');

            return self::FAILURE;
        }

        $valor = ! $this->option('remover');
        $user->forceFill(['is_super_admin' => $valor])->save();

        Log::warning('[Plataforma] acesso de super admin alterado', ['user_id' => $user->id, 'super_admin' => $valor]);
        $this->info($valor
            ? "{$user->email} agora acessa o painel da plataforma (/plataforma)."
            : "{$user->email} não acessa mais o painel da plataforma.");

        return self::SUCCESS;
    }
}
