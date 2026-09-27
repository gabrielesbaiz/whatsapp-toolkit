<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Contracts;

interface QrRenderer
{
    /**
     * @throws \Gabrielesbaiz\WhatsappToolkit\Exceptions\MissingDependencyException
     */
    public function svg(string $payload, ?int $size = null): string;

    public function available(): bool;
}
