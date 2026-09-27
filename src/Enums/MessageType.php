<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Enums;

/**
 * The Cloud API message types this package can send.
 *
 * The value is the literal "type" field Meta expects in the request body.
 */
enum MessageType: string
{
    case Text = 'text';
    case Template = 'template';
    case Image = 'image';
    case Document = 'document';
    case Audio = 'audio';
    case Video = 'video';
    case Sticker = 'sticker';
    case Location = 'location';
    case Contacts = 'contacts';
    case Interactive = 'interactive';
    case Reaction = 'reaction';

    /**
     * Whether the type carries a media object addressed by id or link.
     */
    public function isMedia(): bool
    {
        return in_array($this, [self::Image, self::Document, self::Audio, self::Video, self::Sticker], true);
    }
}
