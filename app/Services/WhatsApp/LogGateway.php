<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Log;

/** Default local driver: writes the message to the log instead of sending it. */
class LogGateway implements WhatsAppGateway
{
    public function send(string $to, string $body): array
    {
        Log::channel(config('logging.default'))->info('[WhatsApp] → '.$to, ['body' => $body]);

        return ['ok' => true, 'response' => ['driver' => 'log']];
    }

    public function name(): string
    {
        return 'log';
    }
}
