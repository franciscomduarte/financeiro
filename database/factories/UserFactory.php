<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /** Vincula o usuário à clínica ativa (se houver), com o papel do campo role (sem role: administrador). */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $clinica = app(ClinicaAtual::class)->get();
            if ($clinica && ! $user->clinicas()->whereKey($clinica->id)->exists()) {
                $user->clinicas()->attach($clinica->id, ['papel' => $user->getRawOriginal('role') ? $user->role->value : 'admin']);
            }
        });
    }

    /** Usuário sem nenhuma clínica vinculada. */
    public function semClinica(): static
    {
        return $this->afterCreating(fn (User $user) => $user->clinicas()->detach());
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
