<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\View\Components;

use Gabrielesbaiz\WhatsappToolkit\Contracts\Whatsapp;
use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;
use Illuminate\View\Component;

/**
 * <x-whatsapp-link :to="$phone" :message="$body">Chatta su WhatsApp</x-whatsapp-link>
 *
 * Hand-written click-to-chat anchors go wrong in the same two ways every time:
 * the number loses its country code, and the "&" between query parameters is
 * emitted raw into HTML. Neither can happen here.
 */
final class WhatsappLink extends Component
{
    public string $url;

    public bool $valid;

    public function __construct(
        Whatsapp $whatsapp,
        public ?string $to = null,
        public ?string $message = null,
        public string $format = 'html',
        public ?string $target = null,
    ) {
        $number = $whatsapp->chat();

        $recipient = $to === null ? null : $whatsapp->tryNumber($to); // @phpstan-ignore-line

        $this->valid = $recipient !== null;

        $chat = $this->valid ? $number->to($recipient) : $number;

        $chat = match ($format) {
            'markdown' => $chat->markdown($message),
            'text' => $chat->text($message),
            default => $chat->html($message),
        };

        if ($target !== null && ($resolved = LinkTarget::tryFrom($target)) !== null) {
            $chat = $chat->target($resolved);
        }

        $this->url = $chat->url();
    }

    public function render(): string
    {
        return 'whatsapp-toolkit::components.link';
    }
}
