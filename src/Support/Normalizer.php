<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Support;

use Gabrielesbaiz\WhatsappToolkit\Contracts\PhoneNormalizer;

/**
 * Turns whatever an application stored into a number WhatsApp can dial.
 *
 * The work is one regex and some prefix arithmetic, but it happens once per
 * row on a list screen, so the result is memoized for the request.
 */
final class Normalizer implements PhoneNormalizer
{
    /** @var array<string, PhoneNumber> */
    private array $parsed = [];

    public function __construct(
        private readonly ?string $defaultCountryCode = null,
        private readonly int $memoSize = 256,
    ) {}

    public function normalize(?string $value): PhoneNumber
    {
        $key = (string) $value;

        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }

        $number = PhoneNumber::parse($value, $this->defaultCountryCode);

        if (count($this->parsed) >= $this->memoSize) {
            array_shift($this->parsed);
        }

        return $this->parsed[$key] = $number;
    }

    public function tryNormalize(?string $value): ?PhoneNumber
    {
        return PhoneNumber::tryParse($value, $this->defaultCountryCode);
    }

    public function defaultCountryCode(): ?string
    {
        return $this->defaultCountryCode;
    }
}
