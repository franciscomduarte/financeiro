<?php

return [
    'url'      => env('EVOLUTION_URL', 'http://localhost:8080'),
    'api_key'  => env('EVOLUTION_API_KEY'),
    'instance' => env('EVOLUTION_INSTANCE', 'financeiro'),
    // Versão principal da Evolution API (1 ou 2): muda o formato do envio de texto
    'versao'   => (int) env('EVOLUTION_VERSAO', 1),
];
