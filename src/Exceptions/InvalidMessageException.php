<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

final class InvalidMessageException extends WhatsappToolkitException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }

    public static function emptyBody(): self
    {
        return new self('A WhatsApp message needs a body. Call text(), html(), markdown() or template() before sending.');
    }

    public static function tooManyButtons(int $count): self
    {
        return new self("WhatsApp accepts at most 3 reply buttons, {$count} given.");
    }
}
