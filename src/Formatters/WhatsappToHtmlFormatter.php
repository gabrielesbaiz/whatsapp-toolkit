<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Formatters;

use Gabrielesbaiz\WhatsappToolkit\Contracts\MessageFormatter;

/**
 * WhatsApp markup rendered back as HTML.
 *
 * The inverse direction is needed the moment an application stores what it
 * sent, or receives a reply through the webhook, and then has to show it in a
 * browser. Input is escaped first, so an inbound message can never inject
 * markup into the page that renders it.
 */
final class WhatsappToHtmlFormatter implements MessageFormatter
{
    /** @var array<string, string> */
    private const array RULES = [
        '/```(.+?)```/s' => '<code>$1</code>',
        '/(?<![\w\*])\*(?!\s)(.+?)(?<!\s)\*(?![\w\*])/s' => '<strong>$1</strong>',
        '/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/s' => '<em>$1</em>',
        '/(?<![\w~])~(?!\s)(.+?)(?<!\s)~(?![\w~])/s' => '<del>$1</del>',
    ];

    public function format(?string $source): string
    {
        if ($source === null || $source === '') {
            return '';
        }

        // Escaping before conversion, never after: anything the sender typed
        // is untrusted content, and the tags added below are the only markup
        // allowed to survive.
        $html = htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        $html = (string) preg_replace(array_keys(self::RULES), array_values(self::RULES), $html);

        // Quoted lines come through as "> ", one level only, which is all
        // WhatsApp itself supports.
        $html = (string) preg_replace('/^&gt;\s?(.*)$/m', '<blockquote>$1</blockquote>', $html);

        return nl2br($html, false);
    }
}
