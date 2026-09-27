<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Contracts;

use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;

/**
 * An outgoing Cloud API message.
 *
 * Implementations validate themselves in their constructor, so a body with
 * four reply buttons fails while it is being built rather than after a round
 * trip to Meta.
 */
interface CloudMessage
{
    public function type(): MessageType;

    public function to(string $recipient): static;

    public function recipient(): ?string;

    /**
     * The request body, minus messaging_product and to, which the client adds.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;
}
