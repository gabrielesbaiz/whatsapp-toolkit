<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns\HasRecipient;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidMessageException;

/**
 * A free-form text message.
 *
 * Deliverable only inside the 24-hour customer service window; outside it,
 * WhatsApp accepts nothing but an approved template.
 */
final class TextMessage implements CloudMessage
{
    use HasRecipient;

    private bool $preview = true;

    private function __construct(public readonly string $body) {}

    public static function make(string $body): self
    {
        $body = trim($body);

        if ($body === '') {
            throw InvalidMessageException::emptyBody();
        }

        if (mb_strlen($body) > 4096) {
            throw InvalidMessageException::because('A WhatsApp text message is limited to 4096 characters.');
        }

        return new self($body);
    }

    /** Whether a URL in the body gets a preview card. */
    public function previewUrl(bool $preview = true): self
    {
        $clone = clone $this;
        $clone->preview = $preview;

        return $clone;
    }

    public function type(): MessageType
    {
        return MessageType::Text;
    }

    public function toPayload(): array
    {
        return [
            'type' => 'text',
            'text' => [
                'preview_url' => $this->preview,
                'body' => $this->body,
            ],
        ];
    }
}
