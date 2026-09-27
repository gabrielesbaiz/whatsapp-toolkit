<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

final class ConfigurationException extends WhatsappToolkitException
{
    public static function cloudDisabled(): self
    {
        return new self('The WhatsApp Cloud API layer is disabled. Set WHATSAPP_CLOUD_ENABLED=true and provide credentials before sending.');
    }

    public static function missing(string $key): self
    {
        return new self("whatsapp-toolkit.{$key} is not configured.");
    }

    public static function missingTemplate(string $name): self
    {
        return new self("No message template named [{$name}] is defined in whatsapp-toolkit.templates.");
    }
}
