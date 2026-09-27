<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

class RateLimitException extends CloudApiException
{
    protected ?int $retryAfter = null;

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * A queued job should release itself for this long rather than retry in
     * place — the limit is per second across the whole business number, so
     * spinning only makes it worse.
     */
    public function withRetryAfter(?int $seconds): static
    {
        $this->retryAfter = $seconds;

        return $this;
    }
}
