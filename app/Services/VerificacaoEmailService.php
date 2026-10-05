<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ConfirmarEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/** Confirmação de e-mail do autocadastro: link assinado que vale por 7 dias e não exige login. */
class VerificacaoEmailService
{
    public const VALIDADE_DIAS = 7;

    public function enviar(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        Mail::to($user->email)->queue(new ConfirmarEmailMail($user->name, $this->link($user)));
    }

    public function link(User $user): string
    {
        return URL::temporarySignedRoute('verificacao.confirmar', now()->addDays(self::VALIDADE_DIAS), [
            'id'   => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }

    /** Confirma se o link corresponde ao e-mail atual do usuário (troca de e-mail invalida links antigos). */
    public function confirmar(User $user, string $hash): bool
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return true;
    }
}
