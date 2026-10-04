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

    /** Papel na clínica informada (ou na clínica ativa). */
    public function papelNa(?Clinica $clinica = null): ?RoleUsuario
    {
        $clinica ??= app(ClinicaAtual::class)->get();
        if ($clinica === null) {
            return null;
        }

        $papel = $this->clinicas()->whereKey($clinica->id)->value('clinica_user.papel');

        return $papel ? RoleUsuario::from($papel) : null;
    }

    /** Administrador da clínica ativa (o papel é por clínica). */
    public function isAdmin(): bool
    {
        return $this->papelNa() === RoleUsuario::Admin;
    }
}
