<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Esqueci minha senha: link por e-mail e nova senha. */
class RedefinirSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_do_email_redefine_a_senha(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@exemplo.com']);

        $this->post('/esqueci-senha', ['email' => 'ana@exemplo.com'])->assertSessionHasNoErrors();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token): bool {
            $token = $n->token;

            return true;
        });

        $this->get('/redefinir-senha/' . $token . '?email=ana@exemplo.com')->assertOk()->assertSee('ana@exemplo.com');
        $this->post('/redefinir-senha', [
            'token' => $token, 'email' => 'ana@exemplo.com', 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123',
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nova-senha-123', $user->fresh()->password));
    }

    public function test_link_antigo_mostra_como_pedir_outro(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.com']);

        $this->from('/redefinir-senha/velho')->post('/redefinir-senha', [
            'token' => 'velho', 'email' => 'Ana@Exemplo.com ', 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123',
        ])->assertRedirect('/redefinir-senha/velho')->assertSessionHasErrors(['token' => 'Este link expirou ou já foi usado. Peça um novo link e use o e-mail mais recente.']);

        $this->followingRedirects()->from('/redefinir-senha/velho')->post('/redefinir-senha', [
            'token' => 'velho', 'email' => 'ana@exemplo.com', 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123',
        ])->assertSee('Pedir um novo link');
    }
}
