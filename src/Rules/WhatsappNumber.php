<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Rules;

use Closure;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a value can address a WhatsApp conversation.
 *
 *     'mobile' => ['required', new WhatsappNumber],
 *
 * It checks shape, not existence: no API can tell you whether a number has
 * WhatsApp installed without messaging it.
 */
final class WhatsappNumber implements ValidationRule
{
    public function __construct(private readonly ?string $countryCode = null) {}

    /**
     * Require the number to be written in international form.
     */
    public static function international(): self
    {
        return new self('');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $default = $this->countryCode;

        if (! is_string($value) && ! is_numeric($value)) {
            $fail('validation.whatsapp_number')->translate(['attribute' => $attribute]);

            return;
        }

        if (PhoneNumber::tryParse((string) $value, $default) === null) {
            $fail('whatsapp-toolkit::validation.whatsapp_number')->translate(['attribute' => $attribute]);
        }
    }
}
