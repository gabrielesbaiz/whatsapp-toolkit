<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Contracts;

use Gabrielesbaiz\WhatsappToolkit\Support\Chat;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;

/**
 * The package's front door.
 *
 * Bound as a singleton and resolved by the facade, so an application can swap
 * it wholesale in a test or replace it with its own implementation without
 * touching a call site.
 */
interface Whatsapp
{
    public function to(string|PhoneNumber|null $recipient): Chat;

    public function chat(): Chat;

    public function number(?string $value): PhoneNumber;

    public function format(?string $html): string;

    public function url(string|PhoneNumber|null $recipient, ?string $html = null): string;
}
