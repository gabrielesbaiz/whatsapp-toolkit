<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Responses;

use Gabrielesbaiz\WhatsappToolkit\Enums\MessageStatus;

/**
 * What Meta returns when a message is accepted.
 *
 * The recipient id is the interesting part and is easy to overlook: it is the
 * canonical wa_id, which for some countries differs from the number that was
 * sent (Argentina and Mexico both have historical quirks). Store this value and
 * address later messages with it rather than with whatever the user typed.
 */
final readonly class MessageResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $recipientId = null,
        public ?string $input = null,
        public ?MessageStatus $status = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(array $body): self
    {
        /** @var array<int, array<string, mixed>> $messages */
        $messages = is_array($body['messages'] ?? null) ? $body['messages'] : [];

        /** @var array<int, array<string, mixed>> $contacts */
        $contacts = is_array($body['contacts'] ?? null) ? $body['contacts'] : [];

        $message = $messages[0] ?? [];
        $contact = $contacts[0] ?? [];

        return new self(
            id: (string) ($message['id'] ?? ''),
            recipientId: isset($contact['wa_id']) ? (string) $contact['wa_id'] : null,
            input: isset($contact['input']) ? (string) $contact['input'] : null,
            status: isset($message['message_status'])
                ? MessageStatus::tryFrom((string) $message['message_status'])
                : null,
            raw: $body,
        );
    }

    public function messageId(): string
    {
        return $this->id;
    }
}
