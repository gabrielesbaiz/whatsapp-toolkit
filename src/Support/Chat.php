<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Support;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TextMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse;
use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageFormat;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException;
use Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * A conversation being described, one call at a time.
 *
 * Every method that changes something returns a new instance, so a chat can be
 * built up once and reused per recipient without the surprise of a shared
 * object mutating underneath a loop.
 *
 * @implements Arrayable<string, string|null>
 */
final class Chat implements Arrayable, Stringable
{
    private ?PhoneNumber $recipient = null;

    private ?string $shortCode = null;

    private ?string $body = null;

    private MessageFormat $format = MessageFormat::Plain;

    private ?LinkTarget $target = null;

    private ?int $limit = null;

    private bool $previewUrl = true;

    public function __construct(private readonly WhatsappToolkit $toolkit) {}

    public function __toString(): string
    {
        return $this->url();
    }

    /**
     * Address the conversation to a number.
     *
     * @throws InvalidPhoneNumberException
     */
    public function to(string|PhoneNumber|null $recipient): self
    {
        $clone = clone $this;

        $clone->recipient = $recipient instanceof PhoneNumber
            ? $recipient
            : $this->toolkit->number($recipient);

        return $clone;
    }

    /**
     * Address a business short link (wa.me/message/XXXXX) instead of a number.
     */
    public function shortCode(string $code): self
    {
        $clone = clone $this;
        $clone->shortCode = $code;
        $clone->target = LinkTarget::Business;

        return $clone;
    }

    /** A body that is already WhatsApp markup, or needs no formatting at all. */
    public function text(?string $body): self
    {
        return $this->withBody($body, MessageFormat::Plain);
    }

    /** A body coming out of a rich-text editor. */
    public function html(?string $body): self
    {
        return $this->withBody($body, MessageFormat::Html);
    }

    /** A body written in Markdown. */
    public function markdown(?string $body): self
    {
        return $this->withBody($body, MessageFormat::Markdown);
    }

    /**
     * A body from the whatsapp-toolkit.templates config, with :placeholders filled.
     *
     * These are local templates for prefilled links. They are unrelated to the
     * approved templates the Cloud API requires, which are sent with
     * WhatsappToolkit::cloud()->send(TemplateMessage::make(...)).
     *
     * @param  array<string, string|int|float|null>  $replacements
     */
    public function template(string $name, array $replacements = []): self
    {
        return $this->withBody(
            $this->toolkit->renderTemplate($name, $replacements),
            MessageFormat::Markdown,
        );
    }

    /** Override the configured link flavour for this chat only. */
    public function target(LinkTarget $target): self
    {
        $clone = clone $this;
        $clone->target = $target;

        return $clone;
    }

    /** Cut the body at this many characters, at a word boundary. */
    public function truncate(int $limit): self
    {
        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    /** Cloud API only: whether a link in the body gets a preview card. */
    public function withoutPreview(): self
    {
        $clone = clone $this;
        $clone->previewUrl = false;

        return $clone;
    }

    /**
     * The formatted body, as plain WhatsApp markup.
     */
    public function message(): Message
    {
        $message = new Message($this->toolkit->formatAs($this->body, $this->format));

        return $message->enforce(
            $this->limit ?? $this->toolkit->maxLength(),
            $this->limit !== null ? 'truncate' : $this->toolkit->overflowStrategy(),
        );
    }

    /**
     * The click-to-chat URL.
     */
    public function url(?LinkTarget $target = null): string
    {
        $target ??= $this->target ?? $this->toolkit->defaultTarget();

        $addressee = $target->usesShortCode()
            ? (string) $this->shortCode
            : ($this->recipient?->waId() ?? '');

        return $target->build($addressee, $this->message()->encoded());
    }

    /**
     * A ready-made anchor, escaped for a Blade template.
     *
     * @param  array<string, string>  $attributes
     */
    public function link(string $label = 'WhatsApp', array $attributes = []): HtmlString
    {
        $attributes = array_merge([
            'href' => $this->url(),
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ], $attributes);

        $rendered = '';

        foreach ($attributes as $key => $value) {
            $rendered .= ' '.$key.'="'.e($value).'"';
        }

        return new HtmlString('<a'.$rendered.'>'.e($label).'</a>');
    }

    /**
     * The chat link as an SVG QR code.
     */
    public function qr(?int $size = null): HtmlString
    {
        return new HtmlString($this->toolkit->qr()->svg($this->url(LinkTarget::WaMe), $size));
    }

    /**
     * The same QR, ready for an <img src="…">.
     */
    public function qrDataUri(?int $size = null): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(
            $this->toolkit->qr()->svg($this->url(LinkTarget::WaMe), $size),
        );
    }

    /**
     * Actually deliver the message through the Cloud API.
     *
     * Free-form text only reaches someone who wrote to you in the last 24
     * hours; outside that window WhatsApp requires an approved template, and
     * the API answers with a ReEngagementRequiredException.
     */
    public function send(): MessageResponse
    {
        if ($this->recipient === null) {
            throw InvalidPhoneNumberException::empty();
        }

        $message = TextMessage::make((string) $this->message())
            ->previewUrl($this->previewUrl)
            ->to($this->recipient->waId());

        return $this->toolkit->cloud()->send($message);
    }

    public function recipient(): ?PhoneNumber
    {
        return $this->recipient;
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'to' => $this->recipient?->e164(),
            'text' => (string) $this->message(),
            'url' => $this->url(),
        ];
    }

    private function withBody(?string $body, MessageFormat $format): self
    {
        $clone = clone $this;
        $clone->body = $body;
        $clone->format = $format;

        return $clone;
    }
}
