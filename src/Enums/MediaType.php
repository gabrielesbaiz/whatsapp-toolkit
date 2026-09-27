<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
    case Sticker = 'sticker';

    public function toMessageType(): MessageType
    {
        return MessageType::from($this->value);
    }

    /** Only a document carries a filename; the others reject it. */
    public function supportsFilename(): bool
    {
        return $this === self::Document;
    }

    /** A sticker has no caption. */
    public function supportsCaption(): bool
    {
        return in_array($this, [self::Image, self::Video, self::Document], true);
    }
}
