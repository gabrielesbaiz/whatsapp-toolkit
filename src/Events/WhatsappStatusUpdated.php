<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Events;

use Gabrielesbaiz\WhatsappToolkit\Webhooks\StatusUpdate;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class WhatsappStatusUpdated
{
    use Dispatchable;

    public function __construct(public StatusUpdate $status) {}
}
