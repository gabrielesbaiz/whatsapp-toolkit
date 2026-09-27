<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Webhooks;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\ErrorPayload;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageStatus;

/**
 * The delivery receipt for a message this application sent.
 */
final readonly class StatusUpdate
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $recipientId,
        public ?MessageStatus $status = null,
        public ?int $timestamp = null,
        public ?string $conversationId = null,
        public ?ErrorPayload $error = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $status
     */
    public static function fromArray(array $status): self
    {
        /** @var array<string, mixed> $conversation */
        $conversation = is_array($status['conversation'] ?? null) ? $status['conversation'] : [];

        /** @var array<int, array<string, mixed>> $errors */
        $errors = is_array($status['errors'] ?? null) ? $status['errors'] : [];

        return new self(
            id: (string) ($status['id'] ?? ''),
            recipientId: (string) ($status['recipient_id'] ?? ''),
            status: isset($status['status']) ? MessageStatus::tryFrom((string) $status['status']) : null,
            timestamp: isset($status['timestamp']) ? (int) $status['timestamp'] : null,
            conversationId: isset($conversation['id']) ? (string) $conversation['id'] : null,
            error: $errors === [] ? null : ErrorPayload::fromArray($errors[0]),
            raw: $status,
        );
    }

    public function failed(): bool
    {
        return $this->status === MessageStatus::Failed;
    }
}
