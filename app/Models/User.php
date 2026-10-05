<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleUsuario;
use App\Support\ClinicaAtual;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'active'])]
// is_super_admin fica fora do Fillable de propósito: só é alterado diretamente
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => RoleUsuario::class,
            'active'            => 'boolean',
            'is_super_admin'    => 'boolean',
        ];
    }

    /** Clínicas que o usuário acessa, com o papel em cada uma (pivot "papel"). */
    public function clinicas(): BelongsToMany
    {
        return $this->belongsToMany(Clinica::class, 'clinica_user')
            ->withPivot('papel')
            ->withTimestamps()
            ->orderBy('nome');
    }

    public function pertenceA(?Clinica $clinica): bool
    {
        return $clinica !== null && $this->clinicas()->whereKey($clinica->id)->exists();
    }

    /** @var array<string, ?RoleUsuario> papel por clínica, lido uma vez por requisição */
    private array $papeis = [];

    /** Papel na clínica informada (ou na clínica ativa). */
    public function papelNa(?Clinica $clinica = null): ?RoleUsuario
    {
        $clinicaAtual = app(ClinicaAtual::class);
        $clinica ??= $clinicaAtual->get();
        if ($clinica === null) {
            return null;
        }

        // Dono da plataforma em modo suporte enxerga como administrador (sempre só leitura)
        if ($this->is_super_admin && $clinicaAtual->emSuporte() && $clinicaAtual->id() === $clinica->id) {
            return RoleUsuario::Admin;
        }

        if (! array_key_exists($clinica->id, $this->papeis)) {
            $papel = $this->clinicas()->whereKey($clinica->id)->value('clinica_user.papel');
            $this->papeis[$clinica->id] = $papel ? RoleUsuario::tryFrom($papel) : null;
        }

        return $this->papeis[$clinica->id];
    }

    /** Esquece os papéis lidos (após alterar o vínculo do usuário). */
    public function esquecerPapeis(): void
    {
        $this->papeis = [];
    }

    /** Administrador da clínica ativa (o papel é por clínica). */
    public function isAdmin(): bool
    {
        return $this->papelNa() === RoleUsuario::Admin;
    }

    /** Acesso à área do sistema na clínica ativa, conforme o perfil. */
    public function pode(\App\Enums\Modulo $modulo): bool
    {
        return (bool) $this->papelNa()?->pode($modulo);
    }

    /** Rota da tela inicial do usuário na clínica ativa. */
    public function paginaInicial(): string
    {
        return $this->papelNa()?->paginaInicial() ?? 'dashboard';
    }
}
