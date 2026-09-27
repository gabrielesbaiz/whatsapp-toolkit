<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Http\Middleware;

use Closure;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\SignatureVerificationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proves a webhook really came from Meta.
 *
 * Two details here are not stylistic. The HMAC is computed over the raw body,
 * because re-encoding the parsed JSON changes key order and unicode escaping
 * and every signature would fail. And the comparison is hash_equals(), because
 * a plain string comparison leaks the correct digest one byte at a time.
 */
final class VerifyWebhookSignature
{
    public function __construct(private readonly Repository $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) $this->config->get('whatsapp-toolkit.webhook.app_secret');

        // An unconfigured secret means the request cannot be verified. That is
        // a reason to reject it, never a reason to skip the check.
        if ($secret === '') {
            throw SignatureVerificationException::missingSecret();
        }

        $provided = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            throw SignatureVerificationException::invalid();
        }

        return $next($request);
    }
}
