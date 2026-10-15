<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Http;

class UltraMsgGateway implements WhatsAppGateway
{
    public function send(string $to, string $body): array
    {
        $config = config('academy.whatsapp.ultramsg');

        $response = Http::asForm()->post("https://api.ultramsg.com/{$config['instance']}/messages/chat", [
            'token' => $config['token'],
            'to' => $to,
            'body' => $body,
        ]);

        return ['ok' => $response->successful(), 'response' => $response->json() ?? []];
    }

    public function name(): string
    {
        return 'ultramsg';
    }
}
