<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Events;

use Gabrielesbaiz\WhatsappToolkit\Webhooks\WebhookPayload;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Every verified webhook, before it is split into messages and statuses.
 *
 * The escape hatch: subscribe to this to reach fields this package does not
 * model yet.
 */
final readonly class WhatsappWebhookReceived
{
    use Dispatchable;

    public function __construct(public WebhookPayload $payload) {}
}
