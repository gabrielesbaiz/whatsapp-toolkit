<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappMessageFailed;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappMessageReceived;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappStatusUpdated;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappWebhookReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

function inboundPayload(string $id = 'wamid.IN1'): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => '9876543210',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['display_phone_number' => '390000000', 'phone_number_id' => '1234567890'],
                    'contacts' => [['profile' => ['name' => 'Mario'], 'wa_id' => '393331234567']],
                    'messages' => [[
                        'from' => '393331234567',
                        'id' => $id,
                        'timestamp' => '1700000000',
                        'type' => 'text',
                        'text' => ['body' => 'Buongiorno'],
                    ]],
                ],
            ]],
        ]],
    ];
}

function statusPayload(string $status = 'delivered'): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => '1234567890'],
                    'statuses' => [[
                        'id' => 'wamid.OUT1',
                        'recipient_id' => '393331234567',
                        'status' => $status,
                        'timestamp' => '1700000001',
                        'conversation' => ['id' => 'conv-1'],
                        'errors' => $status === 'failed' ? [['code' => 131047, 'message' => 'Re-engagement']] : [],
                    ]],
                ],
            ]],
        ]],
    ];
}

function signed(array $payload, string $secret = 'app-secret'): array
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return [$body, ['X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, $secret)]];
}

beforeEach(function () {
    config()->set('whatsapp-toolkit.webhook.enabled', true);
    config()->set('whatsapp-toolkit.webhook.app_secret', 'app-secret');
    config()->set('whatsapp-toolkit.webhook.verify_token', 'verify-me');

    // The provider registers the route at boot; re-register for this test run.
    Route::group([
        'prefix' => 'whatsapp/webhook',
        'middleware' => ['api'],
    ], fn () => require __DIR__.'/../routes/webhook.php');
});

it('ships with the webhook switched off', function () {
    // A publicly reachable POST endpoint must be asked for, never inherited
    // from a composer update.
    expect((require __DIR__.'/../config/whatsapp-toolkit.php')['webhook']['enabled'])->toBeFalse();
});

it('echoes the challenge back as plain text', function () {
    $this->get('whatsapp/webhook?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=12345')
        ->assertOk()
        ->assertSee('12345')
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('refuses a handshake with the wrong token', function () {
    $this->get('whatsapp/webhook?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
        ->assertForbidden();
});

it('rejects a payload whose signature does not match', function () {
    [$body] = signed(inboundPayload());

    $this->call('POST', 'whatsapp/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', 'tampered', 'app-secret'),
    ], $body)->assertForbidden();
});

it('rejects a payload with no signature at all', function () {
    [$body] = signed(inboundPayload());

    $this->call('POST', 'whatsapp/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
        ->assertForbidden();
});

it('rejects everything when no app secret is configured', function () {
    // An unverifiable request is refused, never trusted.
    config()->set('whatsapp-toolkit.webhook.app_secret', '');

    [$body, $headers] = signed(inboundPayload());

    $this->call('POST', 'whatsapp/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X-Hub-Signature-256' => $headers['X-Hub-Signature-256'],
    ], $body)->assertForbidden();
});

it('dispatches an event for an inbound message', function () {
    Event::fake();

    postWebhook(inboundPayload())->assertOk();

    Event::assertDispatched(WhatsappWebhookReceived::class);
    Event::assertDispatched(WhatsappMessageReceived::class, function (WhatsappMessageReceived $event): bool {
        return $event->message->from === '393331234567'
            && $event->message->text === 'Buongiorno'
            && $event->message->contactName === 'Mario'
            && $event->phoneNumberId === '1234567890';
    });
});

it('handles the same message only once', function () {
    Event::fake();

    postWebhook(inboundPayload())->assertOk();
    postWebhook(inboundPayload())->assertOk();

    Event::assertDispatchedTimes(WhatsappMessageReceived::class, 1);
});

it('dispatches delivery receipts', function () {
    Event::fake();

    postWebhook(statusPayload())->assertOk();

    Event::assertDispatched(WhatsappStatusUpdated::class);
    Event::assertNotDispatched(WhatsappMessageFailed::class);
});

it('separates a failure from an ordinary status', function () {
    Event::fake();

    postWebhook(statusPayload('failed'))->assertOk();

    Event::assertDispatched(WhatsappMessageFailed::class, function (WhatsappMessageFailed $event): bool {
        return $event->status->error?->code === 131047;
    });
    Event::assertNotDispatched(WhatsappStatusUpdated::class);
});

it('answers 200 even when a listener explodes', function () {
    // Anything else and Meta redelivers for days, then disables the hook.
    Event::listen(WhatsappMessageReceived::class, fn () => throw new RuntimeException('listener broke'));

    postWebhook(inboundPayload('wamid.BOOM'))->assertOk();
});
