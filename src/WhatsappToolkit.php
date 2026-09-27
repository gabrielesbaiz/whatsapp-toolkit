<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit;

use Gabrielesbaiz\WhatsappToolkit\Cloud\CloudApiFake;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudApi;
use Gabrielesbaiz\WhatsappToolkit\Contracts\PhoneNormalizer;
use Gabrielesbaiz\WhatsappToolkit\Contracts\QrRenderer;
use Gabrielesbaiz\WhatsappToolkit\Contracts\Whatsapp;
use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageFormat;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\ConfigurationException;
use Gabrielesbaiz\WhatsappToolkit\Formatters\HtmlFormatter;
use Gabrielesbaiz\WhatsappToolkit\Formatters\MarkdownFormatter;
use Gabrielesbaiz\WhatsappToolkit\Formatters\WhatsappToHtmlFormatter;
use Gabrielesbaiz\WhatsappToolkit\Support\Chat;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Macroable;

/**
 * Click-to-chat links, WhatsApp formatting, QR codes and Cloud API sending.
 *
 * This is an ordinary object bound as a singleton, not a static class. That is
 * what makes the facade genuinely swappable in tests, and what lets an
 * application replace the phone normalizer or the formatter without
 * subclassing anything.
 */
class WhatsappToolkit implements Whatsapp
{
    use Macroable;

    public function __construct(
        protected readonly Repository $config,
        protected readonly Container $container,
        protected readonly PhoneNormalizer $normalizer,
        protected readonly HtmlFormatter $html,
        protected readonly MarkdownFormatter $markdown,
        protected readonly WhatsappToHtmlFormatter $reverse,
        protected readonly QrRenderer $qr,
    ) {}

    /**
     * Start a chat addressed to someone.
     */
    public function to(string|PhoneNumber|null $recipient): Chat
    {
        return $this->chat()->to($recipient);
    }

    /**
     * Start a chat with no recipient yet — useful for a shared body.
     */
    public function chat(): Chat
    {
        return new Chat($this);
    }

    /**
     * Normalize a phone number into its WhatsApp form.
     */
    public function number(?string $value): PhoneNumber
    {
        return $this->normalizer->normalize($value);
    }

    /**
     * Normalize without throwing; null when the number is unusable.
     */
    public function tryNumber(?string $value): ?PhoneNumber
    {
        return $this->normalizer->tryNormalize($value);
    }

    /**
     * HTML to WhatsApp markup. Returns plain text, never URL-encoded.
     */
    public function format(?string $html): string
    {
        return $this->html->format($html);
    }

    /**
     * Markdown to WhatsApp markup.
     */
    public function formatMarkdown(?string $markdown): string
    {
        return $this->markdown->format($markdown);
    }

    /**
     * WhatsApp markup back to HTML, escaped, for rendering in a browser.
     */
    public function toHtml(?string $text): string
    {
        return $this->reverse->format($text);
    }

    /**
     * Format a body written in the given notation.
     */
    public function formatAs(?string $body, MessageFormat $format): string
    {
        return match ($format) {
            MessageFormat::Html => $this->html->format($body),
            MessageFormat::Markdown => $this->markdown->format($body),
            MessageFormat::Plain => trim((string) $body),
        };
    }

    /**
     * The one-liner, for call sites that want nothing but a URL.
     */
    public function url(string|PhoneNumber|null $recipient, ?string $html = null): string
    {
        return $this->to($recipient)->html($html)->url();
    }

    /**
     * Fill a named template from config with :placeholders.
     *
     * @param  array<string, string|int|float|null>  $replacements
     *
     * @throws ConfigurationException
     */
    public function renderTemplate(string $name, array $replacements = []): string
    {
        $template = $this->config->get("whatsapp-toolkit.templates.{$name}");

        if (! is_string($template)) {
            throw ConfigurationException::missingTemplate($name);
        }

        $keys = [];
        $values = [];

        foreach ($replacements as $key => $value) {
            // Longest key first, so :name does not eat the start of :name_full.
            $keys[':'.$key] = (string) $value;
        }

        uksort($keys, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return strtr($template, $keys);
    }

    /**
     * The Cloud API client, resolved lazily so the layer stays dormant.
     */
    public function cloud(): CloudApi
    {
        return $this->container->make(CloudApi::class);
    }

    /**
     * Swap the Cloud API client for a recorder, Laravel-fake style.
     *
     * Nothing leaves the process afterwards; assertions speak in message
     * objects rather than in HTTP requests.
     */
    public function fake(): CloudApiFake
    {
        $fake = new CloudApiFake;

        $this->container->instance(CloudApi::class, $fake);

        return $fake;
    }

    public function qr(): QrRenderer
    {
        return $this->qr;
    }

    public function defaultTarget(): LinkTarget
    {
        $target = $this->config->get('whatsapp-toolkit.link.target', LinkTarget::WaMe);

        return $target instanceof LinkTarget
            ? $target
            : (LinkTarget::tryFrom((string) $target) ?? LinkTarget::WaMe);
    }

    public function maxLength(): int
    {
        return (int) $this->config->get('whatsapp-toolkit.link.max_length', 4096);
    }

    /**
     * @return 'truncate'|'throw'|'ignore'
     */
    public function overflowStrategy(): string
    {
        $strategy = (string) $this->config->get('whatsapp-toolkit.link.on_overflow', 'truncate');

        return in_array($strategy, ['truncate', 'throw', 'ignore'], true) ? $strategy : 'truncate';
    }
}
