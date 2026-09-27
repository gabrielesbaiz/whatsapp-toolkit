<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Http\Controllers;

use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappMessageFailed;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappMessageReceived;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappStatusUpdated;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappWebhookReceived;
use Gabrielesbaiz\WhatsappToolkit\Webhooks\WebhookPayload;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The inbound half of the Cloud API.
 *
 * It dispatches events and stops. It never replies, never marks a message read
 * and never stores anything — all three are decisions for the application, and
 * a package that made them on its own would be impossible to live with.
 */
final class WebhookController
{
    public function __construct(
        private readonly Repository $config,
        private readonly Dispatcher $events,
        private readonly CacheFactory $cache,
    ) {}

    /**
     * The subscription handshake.
     *
     * Meta sends hub.mode, hub.verify_token and hub.challenge; PHP turns the
     * dots into underscores before they reach the query bag, which is the
     * single most common reason this step fails for people.
     */
    public function verify(Request $request): Response
    {
        $token = (string) $this->config->get('whatsapp-toolkit.webhook.verify_token');

        if ($token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            // Plain text, exactly as received. A JSON-wrapped challenge is rejected.
            return new Response((string) $request->query('hub_challenge'), 200, [
                'Content-Type' => 'text/plain',
            ]);
        }

        return new Response('', 403);
    }

    public function handle(Request $request): Response
    {
        $payload = WebhookPayload::fromArray((array) $request->json()->all());

        // Always 200, always quickly. Meta retries anything else for days and
        // will eventually disable the subscription, so a throwing listener must
        // not become a delivery failure.
        rescue(function () use ($payload): void {
            $this->events->dispatch(new WhatsappWebhookReceived($payload));

            foreach ($payload->messages as $message) {
                if ($this->seen($message->id)) {
                    continue;
                }

                $this->events->dispatch(new WhatsappMessageReceived($message, $payload->phoneNumberId));
            }

            foreach ($payload->statuses as $status) {
                $this->events->dispatch(
                    $status->failed()
                        ? new WhatsappMessageFailed($status)
                        : new WhatsappStatusUpdated($status),
                );
            }
        }, report: true);

        return new Response('', 200);
    }

    /**
     * Whether this message id has already been handled.
     *
     * add() rather than has() plus put(): Meta delivers in parallel and the
     * check-then-set race is real.
     */
    private function seen(string $id): bool
    {
        if ($id === '' || ! $this->config->get('whatsapp-toolkit.webhook.idempotency.enabled', true)) {
            return false;
        }

        $store = $this->cache->store($this->config->get('whatsapp-toolkit.webhook.idempotency.store'));

        return ! $store->add(
            'whatsapp-toolkit:wamid:'.sha1($id),
            true,
            (int) $this->config->get('whatsapp-toolkit.webhook.idempotency.ttl', 86400),
        );
    }
}
