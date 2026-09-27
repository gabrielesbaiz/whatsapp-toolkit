<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Gabrielesbaiz\WhatsappToolkit\Support\Chat to(string|\Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber|null $recipient)
 * @method static \Gabrielesbaiz\WhatsappToolkit\Support\Chat chat()
 * @method static \Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber number(?string $value)
 * @method static \Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber|null tryNumber(?string $value)
 * @method static string format(?string $html)
 * @method static string formatMarkdown(?string $markdown)
 * @method static string toHtml(?string $text)
 * @method static string url(string|\Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber|null $recipient, ?string $html = null)
 * @method static string renderTemplate(string $name, array<string, string|int|float|null> $replacements = [])
 * @method static \Gabrielesbaiz\WhatsappToolkit\Contracts\CloudApi cloud()
 * @method static \Gabrielesbaiz\WhatsappToolkit\Cloud\CloudApiFake fake()
 * @method static \Gabrielesbaiz\WhatsappToolkit\Contracts\QrRenderer qr()
 *
 * @see \Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit
 */
class WhatsappToolkit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit::class;
    }
}
