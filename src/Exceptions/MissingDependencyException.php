<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

final class MissingDependencyException extends WhatsappToolkitException
{
    public static function qrCode(): self
    {
        return new self('QR rendering needs the bacon/bacon-qr-code package. Run: composer require bacon/bacon-qr-code');
    }
}
