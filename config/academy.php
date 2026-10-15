<?php

return [

    'scheduler_secret' => env('CRON_SECRET'),

    /*
    | Google Forms → CRM intake. The Apps Script sends this value in the
    | X-Codex-Secret header (see docs/google-forms.md).
    */
    'google_forms_secret' => env('GOOGLE_FORMS_SECRET', 'change-me'),

    'whatsapp' => [
        // log | meta | ultramsg
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'admin_numbers' => array_filter(explode(',', (string) env('WHATSAPP_ADMIN_NUMBERS', ''))),
        'meta' => [
            'token' => env('WHATSAPP_META_TOKEN'),
            'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
        ],
        'ultramsg' => [
            'instance' => env('ULTRAMSG_INSTANCE'),
            'token' => env('ULTRAMSG_TOKEN'),
        ],
    ],

    'bunny' => [
        'library_id' => env('BUNNY_STREAM_LIBRARY_ID'),
        // Token-authentication key from the Bunny Stream library security tab.
        'token_key' => env('BUNNY_STREAM_TOKEN_KEY'),
        'token_ttl' => (int) env('BUNNY_STREAM_TOKEN_TTL', 3600),
    ],

    'payments' => [
        // fake | paymob | moyasar  (only "fake" ships; the others plug into PaymentGateway)
        'driver' => env('PAYMENT_DRIVER', 'fake'),
    ],
];
