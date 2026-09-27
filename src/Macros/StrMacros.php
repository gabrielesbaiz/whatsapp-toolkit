<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Macros;

use Gabrielesbaiz\WhatsappToolkit\Contracts\Whatsapp;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Small conveniences on Str and Stringable.
 *
 *     str($dealer->mobile_phone)->toWhatsappUrl('Buongiorno');
 *     Str::whatsappNumber($raw);
 */
final class StrMacros
{
    public static function register(Whatsapp $whatsapp): void
    {
        Str::macro('whatsappNumber', fn (?string $value): ?string => PhoneNumber::tryParse($value)?->e164());

        Str::macro('whatsappUrl', fn (?string $phone, ?string $message = null): string => $whatsapp->url($phone, $message));

        Stringable::macro('toWhatsappNumber', function (): Stringable {
            /** @var Stringable $this */
            return new Stringable((string) (PhoneNumber::tryParse((string) $this)?->e164() ?? ''));
        });

        Stringable::macro('toWhatsappUrl', function (?string $message = null) use ($whatsapp): Stringable {
            /** @var Stringable $this */
            return new Stringable($whatsapp->url((string) $this, $message));
        });
    }
}
