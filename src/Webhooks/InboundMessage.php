<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Webhooks;

/**
 * A message someone sent to the business number.
 *
 * Receiving one opens the 24-hour customer service window for that contact,
 * which is the fact most applications actually need to record.
 */
final readonly class InboundMessage
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $from,
        public string $type,
        public ?string $text = null,
        public ?int $timestamp = null,
        public ?string $contactName = null,
        public ?string $repliedTo = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $contact
     */
    public static function fromArray(array $message, array $contact = []): self
    {
        $type = (string) ($message['type'] ?? 'unknown');

        /** @var array<string, mixed> $profile */
        $profile = is_array($contact['profile'] ?? null) ? $contact['profile'] : [];

        /** @var array<string, mixed> $context */
        $context = is_array($message['context'] ?? null) ? $message['context'] : [];

        return new self(
            id: (string) ($message['id'] ?? ''),
            from: (string) ($message['from'] ?? ''),
            type: $type,
            text: self::extractText($message, $type),
            timestamp: isset($message['timestamp']) ? (int) $message['timestamp'] : null,
            contactName: isset($profile['name']) ? (string) $profile['name'] : null,
            repliedTo: isset($context['id']) ? (string) $context['id'] : null,
            raw: $message,
        );
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    /** The id of the media object, when the message carries one. */
    public function mediaId(): ?string
    {
        /** @var array<string, mixed> $media */
        $media = is_array($this->raw[$this->type] ?? null) ? $this->raw[$this->type] : [];

        return isset($media['id']) ? (string) $media['id'] : null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private static function extractText(array $message, string $type): ?string
    {
        /** @var array<string, mixed> $body */
        $body = is_array($message[$type] ?? null) ? $message[$type] : [];

        return match ($type) {
            'text' => isset($body['body']) ? (string) $body['body'] : null,
            'button' => isset($body['text']) ? (string) $body['text'] : null,
            'image', 'video', 'document' => isset($body['caption']) ? (string) $body['caption'] : null,
            default => null,
        };
    }
}
