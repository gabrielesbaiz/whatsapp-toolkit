<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Events;

use Gabrielesbaiz\WhatsappToolkit\Webhooks\StatusUpdate;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A message this application sent could not be delivered.
 *
 * Dispatched instead of WhatsappStatusUpdated for failures, so that the unhappy
 * path can be listened for on its own.
 */
final readonly class WhatsappMessageFailed
{
    use Dispatchable;

    public function __construct(public StatusUpdate $status) {}
}
