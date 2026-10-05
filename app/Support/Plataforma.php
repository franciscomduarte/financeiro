<?php

declare(strict_types=1);

namespace App\Support;

/** Dados de contato do dono da plataforma (config/plataforma.php). */
final class Plataforma
{
    public static function email(): ?string
    {
        return config('plataforma.email') ?: null;
    }

    /** Link do WhatsApp da plataforma já com uma mensagem pronta; null se não configurado. */
    public static function whatsappLink(string $mensagem = ''): ?string
    {
        $numero = preg_replace('/\D/', '', (string) config('plataforma.whatsapp'));
        if ($numero === '') {
            return null;
        }

        return 'https://wa.me/' . $numero . ($mensagem !== '' ? '?text=' . rawurlencode($mensagem) : '');
    }

    public static function mensagemAssinatura(?string $clinica): string
    {
        return trim('Olá! Quero assinar o ' . config('app.name') . ($clinica ? " para a clínica {$clinica}" : '') . '.');
    }
}
