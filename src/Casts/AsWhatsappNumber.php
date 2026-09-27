<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Casts;

use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a phone number in the one form WhatsApp accepts.
 *
 *     protected function casts(): array
 *     {
 *         return ['mobile_phone' => AsWhatsappNumber::class];
 *     }
 *
 * Normalizing on the way in means the column holds E.164 and every later
 * comparison, lookup and link is spared the guesswork. An unparseable value is
 * stored as null rather than silently mangled.
 *
 * @implements CastsAttributes<PhoneNumber|null, string|PhoneNumber|null>
 */
final class AsWhatsappNumber implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        return $value === null ? null : PhoneNumber::tryParse((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return PhoneNumber::tryParse((string) $value)?->e164();
    }
}
