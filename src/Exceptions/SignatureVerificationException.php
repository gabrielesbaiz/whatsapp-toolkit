<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Renders as a bare 403.
 *
 * The response says nothing about why: whoever sent an unsigned request has no
 * business learning how close they were.
 */
final class SignatureVerificationException extends WhatsappToolkitException implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 403;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }

    public static function invalid(): self
    {
        return new self('The X-Hub-Signature-256 header does not match the request body.');
    }

    public static function missingSecret(): self
    {
        return new self('whatsapp-toolkit.webhook.app_secret is not set. An unverifiable webhook is rejected, never trusted.');
    }
}
