<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

final class MessageTooLongException extends WhatsappToolkitException
{
    public static function make(int $length, int $limit): self
    {
        return new self("The message is {$length} characters long; the configured limit is {$limit}. Call truncate() on the builder, or set whatsapp-toolkit.link.on_overflow to 'truncate'.");
    }
}
