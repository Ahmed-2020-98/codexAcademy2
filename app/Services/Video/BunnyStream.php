<?php

namespace App\Services\Video;

/**
 * Builds Bunny Stream iframe URLs. When a token key is configured, URLs are
 * signed (token = sha256(key + videoId + expires)) so they cannot be shared.
 */
class BunnyStream
{
    public function isConfigured(): bool
    {
        return filled(config('academy.bunny.library_id'));
    }

    public function embedUrl(?string $videoId): ?string
    {
        if (blank($videoId) || ! $this->isConfigured()) {
            return null;
        }

        $library = config('academy.bunny.library_id');
        $url = "https://iframe.mediadelivery.net/embed/{$library}/{$videoId}";
        $params = ['autoplay' => 'false', 'preload' => 'true', 'responsive' => 'true'];

        if ($key = config('academy.bunny.token_key')) {
            $expires = now()->addSeconds(config('academy.bunny.token_ttl'))->timestamp;
            $params['token'] = hash('sha256', $key.$videoId.$expires);
            $params['expires'] = $expires;
        }

        return $url.'?'.http_build_query($params);
    }
}
