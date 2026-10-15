<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Http;

class MetaCloudGateway implements WhatsAppGateway
{
    public function send(string $to, string $body): array
    {
        $config = config('academy.whatsapp.meta');

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/v21.0/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($to, '+'),
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        return ['ok' => $response->successful(), 'response' => $response->json() ?? []];
    }

    public function name(): string
    {
        return 'meta';
    }
}
