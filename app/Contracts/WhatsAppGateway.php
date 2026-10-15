<?php

namespace App\Contracts;

interface WhatsAppGateway
{
    /**
     * @return array{ok: bool, response: array<string, mixed>}
     */
    public function send(string $to, string $body): array;

    public function name(): string;
}
