<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Enums;

/**
 * The notation a message body is written in before it becomes WhatsApp markup.
 */
enum MessageFormat: string
{
    /** Already WhatsApp markup (*bold*, _italic_). Passed through untouched. */
    case Plain = 'plain';

    /** Rich-text editor output. The common case in admin panels. */
    case Html = 'html';

    /** Markdown, as written in config files, mail templates or seeds. */
    case Markdown = 'markdown';
}
