<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        // Assistente que responde os leads no WhatsApp
        'modelo_assistente'  => env('ANTHROPIC_MODELO_ASSISTENTE', 'claude-sonnet-5-5'),
        'esforco_assistente' => env('ANTHROPIC_ESFORCO_ASSISTENTE', 'low'),
        'espera_assistente'  => (int) env('ANTHROPIC_ESPERA_ASSISTENTE', 15), // segundos para juntar mensagens seguidas
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    // Monitor do sistema (sistema:monitorar): quem recebe os alertas além dos super admins
    'monitor' => [
        'emails'           => env('MONITOR_EMAILS'),             // separados por vírgula
        'whatsapp_clinica' => env('MONITOR_WHATSAPP_CLINICA'),   // slug da clínica cuja instância envia ao "WhatsApp da gestão"
    ],

    // Webhook transacional da Brevo (entrega/abertura dos e-mails): /api/webhooks/brevo/{token}
    'brevo' => [
        'webhook_token' => env('BREVO_WEBHOOK_TOKEN'),
    ],

    'whatsapp' => [
        'allowed_number' => env('WHATSAPP_ALLOWED_NUMBER'),
        'webhook_token'  => env('WHATSAPP_WEBHOOK_TOKEN'),
    ],

];
