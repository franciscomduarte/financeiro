<?php

declare(strict_types=1);

/*
| Dono da plataforma (quem vende o sistema para as clínicas).
| Contato exibido quando o teste grátis termina e destino dos avisos de novos cadastros.
*/
return [
    'email'    => env('PLATAFORMA_EMAIL'),
    'whatsapp' => env('PLATAFORMA_WHATSAPP'), // só números, com DDI (ex.: 5561999990000)
];
