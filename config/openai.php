<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Masukkan API Key kamu di sini, bisa langsung atau melalui .env.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Organization ID (opsional)
    |--------------------------------------------------------------------------
    |
    | Hanya isi jika akunmu punya organization ID.
    |
    */

    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Base URL (opsional)
    |--------------------------------------------------------------------------
    |
    | Ganti jika kamu pakai proxy server (misal local endpoint Ollama).
    |
    */

    'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
];
