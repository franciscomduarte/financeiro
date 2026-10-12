<?php

// Ritmo das campanhas de WhatsApp: devagar e em horário comercial para o número não ser bloqueado
return [
    'intervalo_min_segundos' => (int) env('CAMPANHA_INTERVALO_MIN', 30),
    'intervalo_max_segundos' => (int) env('CAMPANHA_INTERVALO_MAX', 90),
    'limite_diario'          => (int) env('CAMPANHA_LIMITE_DIARIO', 150),
    'hora_inicio'            => 9,  // envia a partir das 9h
    'hora_fim'               => 19, // e para às 19h (horário de Brasília)
    'dias_semana'            => [1, 2, 3, 4, 5, 6], // segunda a sábado (ISO: 1 = segunda)
    'imagem_max_kb'          => 5120,
];
