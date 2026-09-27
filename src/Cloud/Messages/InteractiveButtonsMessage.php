<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns\HasRecipient;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidMessageException;

/**
 * A message with up to three reply buttons.
 *
 * The limits are WhatsApp's and are enforced here rather than discovered from
 * a 400: three buttons, twenty characters per title, and unique ids.
 */
final class InteractiveButtonsMessage implements CloudMessage
{
    use HasRecipient;

    /** @var array<int, array{id: string, title: string}> */
    private array $buttons = [];

    private ?string $header = null;

    private ?string $footer = null;

    private function __construct(public readonly string $body) {}

    public static function make(string $body): self
    {
        if (mb_strlen($body) > 1024) {
            throw InvalidMessageException::because('An interactive message body is limited to 1024 characters.');
        }

        return new self($body);
    }

    public function button(string $id, string $title): self
    {
        if (mb_strlen($title) > 20) {
            throw InvalidMessageException::because("The button title [{$title}] exceeds the 20 character limit.");
        }

        $clone = clone $this;
        $clone->buttons[] = ['id' => $id, 'title' => $title];

        if (count($clone->buttons) > 3) {
            throw InvalidMessageException::tooManyButtons(count($clone->buttons));
        }

        return $clone;
    }

    public function header(?string $header): self
    {
        $clone = clone $this;
        $clone->header = $header;

        return $clone;
    }

    public function footer(?string $footer): self
    {
        $clone = clone $this;
        $clone->footer = $footer;

        return $clone;
    }

    public function type(): MessageType
    {
        return MessageType::Interactive;
    }

    public function toPayload(): array
    {
        if ($this->buttons === []) {
            throw InvalidMessageException::because('An interactive buttons message needs at least one button.');
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $this->body],
            'action' => [
                'buttons' => array_map(
                    static fn (array $button): array => ['type' => 'reply', 'reply' => $button],
                    $this->buttons,
                ),
            ],
        ];

        if ($this->header !== null) {
            $interactive['header'] = ['type' => 'text', 'text' => $this->header];
        }

        if ($this->footer !== null) {
            $interactive['footer'] = ['text' => $this->footer];
        }

        return ['type' => 'interactive', 'interactive' => $interactive];
    }
}
