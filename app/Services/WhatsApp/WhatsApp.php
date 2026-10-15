<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/** Sends through the configured gateway and keeps an outbound log for the admin panel. */
class WhatsApp
{
    public function __construct(private WhatsAppGateway $gateway) {}

    public function send(?string $to, string $body, ?string $event = null, ?Model $related = null): ?WhatsappMessage
    {
        if (blank($to)) {
            return null;
        }

        $message = WhatsappMessage::create([
            'to' => $to,
            'body' => $body,
            'event' => $event,
            'provider' => $this->gateway->name(),
            'status' => 'queued',
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
        ]);

        try {
            $result = $this->gateway->send($to, $body);
            $message->update([
                'status' => $result['ok'] ? 'sent' : 'failed',
                'response' => $result['response'],
                'sent_at' => $result['ok'] ? now() : null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $message->update(['status' => 'failed', 'response' => ['error' => $e->getMessage()]]);
        }

        return $message;
    }

    public function toAdmins(string $body, ?string $event = null, ?Model $related = null): void
    {
        foreach (config('academy.whatsapp.admin_numbers') as $number) {
            $this->send(trim($number), $body, $event, $related);
        }
    }
}
