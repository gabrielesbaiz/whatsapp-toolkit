<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Events;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class WhatsappMessageSent
{
    use Dispatchable;

    public function __construct(
        public CloudMessage $message,
        public MessageResponse $response,
    ) {}
}
