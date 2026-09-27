<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns\HasRecipient;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;

/**
 * An emoji reaction to a message you know the id of.
 *
 * An empty emoji removes a reaction. Reacting to something older than 30 days
 * is accepted and then quietly does nothing.
 */
final class ReactionMessage implements CloudMessage
{
    use HasRecipient;

    private function __construct(
        public readonly string $messageId,
        public readonly string $emoji,
    ) {}

    public static function make(string $messageId, string $emoji): self
    {
        return new self($messageId, $emoji);
    }

    public static function remove(string $messageId): self
    {
        return new self($messageId, '');
    }

    public function type(): MessageType
    {
        return MessageType::Reaction;
    }

    public function toPayload(): array
    {
        return [
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $this->messageId,
                'emoji' => $this->emoji,
            ],
        ];
    }
}
