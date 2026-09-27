<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns\HasRecipient;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;

/**
 * An approved template — the only thing that reaches someone who has not
 * written to you in the last 24 hours.
 *
 * Templates are created and approved in the WhatsApp Manager; the API will not
 * create one on the fly. Two things trip people up constantly: the language
 * code must match the approved variant exactly ("it" and "it_IT" are different
 * templates), and the parameter count must match the body's placeholders
 * exactly or Meta answers 132000.
 */
final class TemplateMessage implements CloudMessage
{
    use HasRecipient;

    /** @var array<int, array<string, mixed>> */
    private array $components = [];

    private function __construct(
        public readonly string $name,
        public readonly string $language,
    ) {}

    public static function make(string $name, string $language = 'en_US'): self
    {
        return new self($name, $language);
    }

    public function language(string $language): self
    {
        return (new self($this->name, $language))->withComponents($this->components);
    }

    /**
     * Positional body parameters, in the order the template declares them.
     */
    public function body(string|int|float ...$parameters): self
    {
        return $this->component('body', array_map(
            static fn (string|int|float $value): array => ['type' => 'text', 'text' => (string) $value],
            $parameters,
        ));
    }

    /**
     * Header parameters. Media headers take a link or a media id.
     *
     * @param  array<int, array<string, mixed>>|string  $parameters
     */
    public function header(array|string ...$parameters): self
    {
        return $this->component('header', array_map(
            static fn (array|string $value): array => is_array($value)
                ? $value
                : ['type' => 'text', 'text' => $value],
            $parameters,
        ));
    }

    /**
     * A quick-reply or URL button parameter, by zero-based index.
     */
    public function button(int $index, string $payload, string $subType = 'quick_reply'): self
    {
        $clone = clone $this;

        $clone->components[] = [
            'type' => 'button',
            'sub_type' => $subType,
            'index' => (string) $index,
            'parameters' => [[
                'type' => $subType === 'url' ? 'text' : 'payload',
                $subType === 'url' ? 'text' : 'payload' => $payload,
            ]],
        ];

        return $clone;
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    public function withComponents(array $components): self
    {
        $clone = clone $this;
        $clone->components = $components;

        return $clone;
    }

    public function type(): MessageType
    {
        return MessageType::Template;
    }

    public function toPayload(): array
    {
        $template = [
            'name' => $this->name,
            'language' => ['code' => $this->language],
        ];

        if ($this->components !== []) {
            $template['components'] = $this->components;
        }

        return [
            'type' => 'template',
            'template' => $template,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $parameters
     */
    private function component(string $type, array $parameters): self
    {
        $clone = clone $this;

        $clone->components[] = [
            'type' => $type,
            'parameters' => $parameters,
        ];

        return $clone;
    }
}
