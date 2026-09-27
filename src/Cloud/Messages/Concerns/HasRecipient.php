<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns;

use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;

trait HasRecipient
{
    protected ?string $recipient = null;

    /**
     * Address this message.
     *
     * A value that is already all digits is taken as a canonical wa_id and left
     * alone — Meta's own identifier must never be re-derived.
     */
    public function to(string $recipient): static
    {
        $clone = clone $this;

        $clone->recipient = ctype_digit($recipient)
            ? $recipient
            : PhoneNumber::parse($recipient)->waId();

        return $clone;
    }

    public function recipient(): ?string
    {
        return $this->recipient;
    }
}
