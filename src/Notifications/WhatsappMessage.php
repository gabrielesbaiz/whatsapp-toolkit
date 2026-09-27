<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Notifications;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\MediaMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TemplateMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TextMessage;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MediaType;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidMessageException;

/**
 * The notification-shaped way to describe a WhatsApp message.
 *
 * A notification may also return any Cloud message object directly; this exists
 * because a one-line `WhatsappMessage::text('…')` reads better in toWhatsapp()
 * and because a template is the thing most notifications actually need.
 */
final class WhatsappMessage
{
    private ?CloudMessage $message = null;

    private ?string $recipient = null;

    public static function text(string $body): self
    {
        $instance = new self;
        $instance->message = TextMessage::make($body);

        return $instance;
    }

    /**
     * An approved Cloud API template — the only message that reaches a contact
     * outside the 24-hour window.
     */
    public static function template(string $name, string $language = 'en_US'): self
    {
        $instance = new self;
        $instance->message = TemplateMessage::make($name, $language);

        return $instance;
    }

    public static function media(MediaType $type, string $idOrLink): self
    {
        $instance = new self;

        $instance->message = str_starts_with($idOrLink, 'http')
            ? MediaMessage::link($type, $idOrLink)
            : MediaMessage::id($type, $idOrLink);

        return $instance;
    }

    /** Template body parameters, in declaration order. */
    public function body(string|int|float ...$parameters): self
    {
        if (! $this->message instanceof TemplateMessage) {
            throw InvalidMessageException::because('body() takes template parameters and only applies to a template message.');
        }

        $this->message = $this->message->body(...$parameters);

        return $this;
    }

    public function language(string $language): self
    {
        if (! $this->message instanceof TemplateMessage) {
            throw InvalidMessageException::because('language() only applies to a template message.');
        }

        $this->message = $this->message->language($language);

        return $this;
    }

    public function caption(string $caption): self
    {
        if (! $this->message instanceof MediaMessage) {
            throw InvalidMessageException::because('caption() only applies to a media message.');
        }

        $this->message = $this->message->caption($caption);

        return $this;
    }

    /** Override the notifiable's own routing for this message. */
    public function to(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function recipient(): ?string
    {
        return $this->recipient;
    }

    public function toMessage(): CloudMessage
    {
        if ($this->message === null) {
            throw InvalidMessageException::emptyBody();
        }

        return $this->message;
    }
}
