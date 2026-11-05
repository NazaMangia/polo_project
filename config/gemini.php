<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver de IA ('gemini' o 'fake')
    |--------------------------------------------------------------------------
    | Si es 'auto', usa 'gemini' cuando GEMINI_API_KEY está configurada y
    | 'fake' en caso contrario (útil para desarrollo local sin gastar cuota).
    */
    'driver' => env('GEMINI_DRIVER', 'auto'),

    'api_key' => env('GEMINI_API_KEY', ''),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),

    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    'temperature' => (float) env('GEMINI_TEMPERATURE', 0.7),

    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 600),

    'timeout' => (int) env('GEMINI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Límites del simulador
    |--------------------------------------------------------------------------
    */
    'max_caracteres_mensaje' => (int) env('SIMULADOR_MAX_CARACTERES', 1000),

    'max_mensajes_sesion' => (int) env('SIMULADOR_MAX_MENSAJES', 80),
];
