<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\ErrorPayload;
use Gabrielesbaiz\WhatsappToolkit\Enums\CloudErrorCode;

/**
 * Anything Meta refused.
 *
 * The payload is carried rather than flattened into a string, because the
 * fbtrace id is what Meta support asks for first and the error code is what a
 * queued job needs in order to decide between releasing and failing.
 *
 * @phpstan-consistent-constructor
 */
abstract class CloudApiException extends WhatsappToolkitException
{
    protected ErrorPayload $payload;

    protected int $status = 0;

    public static function fromPayload(ErrorPayload $payload, int $status = 0): static
    {
        $exception = new static($payload->describe(), $payload->code ?? 0);

        $exception->payload = $payload;
        $exception->status = $status;

        return $exception;
    }

    public function payload(): ErrorPayload
    {
        return $this->payload;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?CloudErrorCode
    {
        return $this->payload->errorCode();
    }

    public function fbtraceId(): ?string
    {
        return $this->payload->fbtraceId;
    }

    public function isRetryable(): bool
    {
        return $this->payload->isRetryable();
    }
}
