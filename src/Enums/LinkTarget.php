<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Enums;

/**
 * The flavours of click-to-chat link WhatsApp understands.
 *
 * They all open the same conversation; they differ in which client picks the
 * link up and in how long the resulting URL is — which matters when the link
 * has to survive a QR code or an SMS.
 */
enum LinkTarget: string
{
    /**
     * The short form. Fewest characters, so the densest QR code, and the one
     * WhatsApp itself hands out. This is the default.
     */
    case WaMe = 'wa.me';

    /** The long form this package used before v2. Kept for continuity. */
    case Api = 'api';

    /** Forces WhatsApp Web, useful for desktop-only back-office screens. */
    case Web = 'web';

    /** Native deep link. Opens the installed app directly, never a browser. */
    case Deep = 'deep';

    /** A business short link (wa.me/message/XXXX), where the code replaces the number. */
    case Business = 'business';

    /**
     * Build the link for a number already reduced to digits.
     */
    public function build(string $digits, string $encodedText): string
    {
        $query = $encodedText === '' ? '' : '?text='.$encodedText;

        return match ($this) {
            self::WaMe => "https://wa.me/{$digits}{$query}",
            self::Business => "https://wa.me/message/{$digits}{$query}",
            self::Api => 'https://api.whatsapp.com/send?phone='.$digits.($encodedText === '' ? '' : '&text='.$encodedText),
            self::Web => 'https://web.whatsapp.com/send?phone='.$digits.($encodedText === '' ? '' : '&text='.$encodedText),
            self::Deep => 'whatsapp://send?phone='.$digits.($encodedText === '' ? '' : '&text='.$encodedText),
        };
    }

    /**
     * Whether this target addresses a business short code rather than a number.
     */
    public function usesShortCode(): bool
    {
        return $this === self::Business;
    }
}
