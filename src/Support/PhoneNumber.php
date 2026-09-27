<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Support;

use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException;
use Stringable;

/**
 * A phone number reduced to the one shape WhatsApp accepts.
 *
 * WhatsApp addresses a person by digits only — no plus, no spaces, no dashes,
 * no parentheses. Version 1 of this package merely url-encoded whatever it was
 * given, so "+39 333 123 4567" travelled as "%2B39+333+123+4567" and opened a
 * chat with nobody. Normalization happens here, once, and every other class
 * takes this object rather than a string.
 */
final class PhoneNumber implements Stringable
{
    /**
     * The country code applied when a number arrives without one.
     *
     * Set once by the service provider from config. It lives here as a static
     * because the value objects are created in places that have no container to
     * ask — a message's to(), an Eloquent cast, a Str macro — and threading a
     * normalizer through all of them would buy nothing.
     */
    public static ?string $defaultCountryCode = null;

    /**
     * @param  string  $digits  E.164 without the leading plus.
     */
    private function __construct(
        public readonly string $digits,
        public readonly ?string $countryCode = null,
    ) {}

    public function __toString(): string
    {
        return $this->e164();
    }

    /**
     * Normalize a raw, human-entered number.
     *
     * @param  string|null  $defaultCountryCode  Applied when the input is a bare local number.
     *
     * @throws InvalidPhoneNumberException
     */
    public static function parse(?string $value, ?string $defaultCountryCode = null): self
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw InvalidPhoneNumberException::empty();
        }

        $hadPlus = str_starts_with($value, '+');

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            throw InvalidPhoneNumberException::notNumeric($value);
        }

        // An explicit empty string means "no default": the caller wants the
        // number to carry its own country code or be refused.
        $defaultCountryCode = $defaultCountryCode === '' ? null : ($defaultCountryCode ?? self::$defaultCountryCode);

        $default = $defaultCountryCode === null ? null : preg_replace('/\D+/', '', $defaultCountryCode);
        $default = ($default === '' ? null : $default);

        // "00" is the international access prefix in most of the world and is
        // never part of the number itself.
        if (! $hadPlus && str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $hadPlus = true;
        }

        if (! $hadPlus && $default !== null) {
            // A national trunk zero disappears when the number goes
            // international; the Italian mobile leading 3, by contrast, is part
            // of the number, so only a leading zero is stripped.
            $local = ltrim($digits, '0');

            // Whether a bare number already carries its country code cannot be
            // decided with certainty — an Italian landline in Monza starts 039
            // and an Italian number written without a plus starts 39. Length
            // settles it: a national number that long does not exist, so the
            // prefix must be the country code.
            $digits = str_starts_with($local, $default) && strlen($local) >= 11
                ? $local
                : $default.$local;

            $hadPlus = true;
        }

        if (! $hadPlus && $default === null) {
            throw InvalidPhoneNumberException::missingCountryCode($digits);
        }

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            throw InvalidPhoneNumberException::outOfRange($digits);
        }

        return new self($digits, $default);
    }

    /**
     * Normalize without throwing. Returns null when the input cannot be used.
     */
    public static function tryParse(?string $value, ?string $defaultCountryCode = null): ?self
    {
        try {
            return self::parse($value, $defaultCountryCode);
        } catch (InvalidPhoneNumberException) {
            return null;
        }
    }

    /**
     * Wrap a value already known to be canonical, skipping normalization.
     *
     * Use this for a wa_id echoed back by Meta: that value is authoritative and
     * must not be re-derived.
     */
    public static function fromWaId(string $waId): self
    {
        return new self($waId);
    }

    /** International form, with the plus. */
    public function e164(): string
    {
        return '+'.$this->digits;
    }

    /** What WhatsApp itself wants: digits, nothing else. */
    public function waId(): string
    {
        return $this->digits;
    }

    /** The number without the configured country code, when it carries one. */
    public function national(): string
    {
        if ($this->countryCode !== null && str_starts_with($this->digits, $this->countryCode)) {
            return substr($this->digits, strlen($this->countryCode));
        }

        return $this->digits;
    }

    public function equals(self $other): bool
    {
        return $this->digits === $other->digits;
    }
}
