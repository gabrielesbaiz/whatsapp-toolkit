<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Webhooks;

/**
 * Meta's webhook envelope, flattened into the two things it ever carries.
 *
 * The structure nests four levels deep — entry, changes, value, messages — and
 * batches unrelated events together, so unpacking it once here keeps every
 * listener from re-learning the shape.
 */
final readonly class WebhookPayload
{
    /**
     * @param  array<int, InboundMessage>  $messages
     * @param  array<int, StatusUpdate>  $statuses
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public array $messages = [],
        public array $statuses = [],
        public ?string $phoneNumberId = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(array $body): self
    {
        $messages = [];
        $statuses = [];
        $phoneNumberId = null;

        /** @var array<int, array<string, mixed>> $entries */
        $entries = is_array($body['entry'] ?? null) ? $body['entry'] : [];

        foreach ($entries as $entry) {
            /** @var array<int, array<string, mixed>> $changes */
            $changes = is_array($entry['changes'] ?? null) ? $entry['changes'] : [];

            foreach ($changes as $change) {
                /** @var array<string, mixed> $value */
                $value = is_array($change['value'] ?? null) ? $change['value'] : [];

                /** @var array<string, mixed> $metadata */
                $metadata = is_array($value['metadata'] ?? null) ? $value['metadata'] : [];

                $phoneNumberId ??= isset($metadata['phone_number_id']) ? (string) $metadata['phone_number_id'] : null;

                /** @var array<int, array<string, mixed>> $contacts */
                $contacts = is_array($value['contacts'] ?? null) ? $value['contacts'] : [];

                /** @var array<int, array<string, mixed>> $incoming */
                $incoming = is_array($value['messages'] ?? null) ? $value['messages'] : [];

                foreach ($incoming as $index => $message) {
                    $messages[] = InboundMessage::fromArray($message, $contacts[$index] ?? $contacts[0] ?? []);
                }

                /** @var array<int, array<string, mixed>> $incomingStatuses */
                $incomingStatuses = is_array($value['statuses'] ?? null) ? $value['statuses'] : [];

                foreach ($incomingStatuses as $status) {
                    $statuses[] = StatusUpdate::fromArray($status);
                }
            }
        }

        return new self($messages, $statuses, $phoneNumberId, $body);
    }

    public function isEmpty(): bool
    {
        return $this->messages === [] && $this->statuses === [];
    }
}
