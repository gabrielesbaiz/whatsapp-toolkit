<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Exceptions;

final class InvalidPhoneNumberException extends WhatsappToolkitException
{
    public static function empty(): self
    {
        return new self('A WhatsApp recipient is required, but the given phone number was empty.');
    }

    public static function notNumeric(string $value): self
    {
        return new self("The phone number [{$value}] contains no digits.");
    }

    public static function outOfRange(string $digits): self
    {
        return new self(sprintf(
            'The phone number [%s] is %d digits long; E.164 allows 7 to 15.',
            $digits,
            strlen($digits),
        ));
    }

    public static function missingCountryCode(string $digits): self
    {
        return new self("The phone number [{$digits}] has no country code and no default is configured. Set whatsapp-toolkit.default_country_code or pass a number in international form.");
    }

    public static function missingRoute(string $notifiable): self
    {
        return new self("[{$notifiable}] must implement routeNotificationForWhatsapp() or the message must carry a recipient.");
    }
}
