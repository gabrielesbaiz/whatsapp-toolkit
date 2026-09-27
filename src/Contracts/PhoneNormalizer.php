<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Contracts;

use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;

interface PhoneNormalizer
{
    /**
     * @throws \Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException
     */
    public function normalize(?string $value): PhoneNumber;

    public function tryNormalize(?string $value): ?PhoneNumber;
}
