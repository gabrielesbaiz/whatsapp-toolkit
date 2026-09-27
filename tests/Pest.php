<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Http;

uses(TestCase::class)->in(__DIR__);

/**
 * Turn the Cloud API on with credentials that are obviously fake.
 */
function enableCloud(array $overrides = []): void
{
    config(array_merge([
        'whatsapp-toolkit.cloud.enabled' => true,
        'whatsapp-toolkit.cloud.access_token' => 'test-token',
        'whatsapp-toolkit.cloud.phone_number_id' => '1234567890',
        'whatsapp-toolkit.cloud.business_account_id' => '9876543210',
        'whatsapp-toolkit.cloud.retry.times' => 1,
    ], $overrides));
}

/**
 * The shape Meta answers with when a message is accepted.
 */
function graphAccepted(string $waId = '393331234567', string $id = 'wamid.TEST'): array
{
    return [
        'messaging_product' => 'whatsapp',
        'contacts' => [['input' => $waId, 'wa_id' => $waId]],
        'messages' => [['id' => $id]],
    ];
}

/**
 * The shape Meta answers with when it refuses.
 */
function graphError(int $code, string $message = 'Something went wrong'): array
{
    return [
        'error' => [
            'message' => $message,
            'type' => 'OAuthException',
            'code' => $code,
            'fbtrace_id' => 'Az1234',
        ],
    ];
}

function fakeGraph(array $body, int $status = 200): void
{
    Http::fake(['graph.facebook.com/*' => Http::response($body, $status)]);
}

/**
 * POST a webhook payload with a valid Meta signature.
 */
function postWebhook(array $payload, string $secret = 'app-secret'): Illuminate\Testing\TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return test()->call('POST', 'whatsapp/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, $secret),
    ], $body);
}
