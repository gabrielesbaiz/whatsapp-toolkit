<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Contracts;

/**
 * Turns some source notation into WhatsApp's own markup.
 *
 * Implementations return plain text. URL encoding is deliberately not their
 * business: the same formatted body has to be usable in a click-to-chat link,
 * in a Cloud API request and in a log line, and only the first of those wants
 * percent escapes.
 */
interface MessageFormatter
{
    public function format(?string $source): string;
}
