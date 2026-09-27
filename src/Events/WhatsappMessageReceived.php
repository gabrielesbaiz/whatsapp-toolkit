<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Events;

use Gabrielesbaiz\WhatsappToolkit\Webhooks\InboundMessage;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Someone wrote to the business number.
 *
 * Carries value objects only, never the request, so a listener can be queued.
 */
final readonly class WhatsappMessageReceived
{
    use Dispatchable;

    public function __construct(
        public InboundMessage $message,
        public ?string $phoneNumberId = null,
    ) {}
}
