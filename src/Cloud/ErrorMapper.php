<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\ErrorPayload;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\CloudApiException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\RateLimitException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\TransientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * One readable table from Meta's error envelope to this package's exceptions.
 *
 * It also answers the only question the retry logic cares about: is another
 * attempt worth making, or will it fail identically?
 */
final class ErrorMapper
{
    public function fromResponse(Response $response): CloudApiException
    {
        $payload = ErrorPayload::fromArray((array) $response->json());

        $class = $payload->errorCode()?->exceptionClass() ?? TransientException::class;

        $exception = $class::fromPayload($payload, $response->status());

        if ($exception instanceof RateLimitException) {
            $retryAfter = $response->header('Retry-After');

            return $exception->withRetryAfter($retryAfter === '' ? null : (int) $retryAfter);
        }

        return $exception;
    }

    /**
     * Whether a failure deserves another attempt.
     *
     * A connection error or a 5xx is the server's problem and may well pass. An
     * expired token, a rejected template or an unknown recipient is a fact
     * about the request, and retrying it only spends quota.
     */
    public function isRetryable(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if (! $e instanceof RequestException) {
            return false;
        }

        if ($e->response->serverError()) {
            return true;
        }

        return ErrorPayload::fromArray((array) $e->response->json())->isRetryable();
    }
}
