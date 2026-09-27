<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns\HasRecipient;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MediaType;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidMessageException;

/**
 * An image, video, audio file, document or sticker.
 *
 * Sending by id is the reliable way: Meta fetches a link synchronously while
 * your request waits, so a slow origin becomes your timeout. Upload once with
 * uploadMedia() and reuse the id — bearing in mind ids expire after 30 days.
 */
final class MediaMessage implements CloudMessage
{
    use HasRecipient;

    private ?string $caption = null;

    private ?string $filename = null;

    private function __construct(
        public readonly MediaType $media,
        public readonly ?string $id,
        public readonly ?string $link,
    ) {}

    public static function id(MediaType $media, string $id): self
    {
        return new self($media, $id, null);
    }

    public static function link(MediaType $media, string $url): self
    {
        if (! str_starts_with($url, 'https://')) {
            throw InvalidMessageException::because('Media links must be publicly reachable over HTTPS.');
        }

        return new self($media, null, $url);
    }

    public function caption(?string $caption): self
    {
        if ($caption !== null && ! $this->media->supportsCaption()) {
            throw InvalidMessageException::because("A {$this->media->value} message cannot carry a caption.");
        }

        $clone = clone $this;
        $clone->caption = $caption;

        return $clone;
    }

    public function filename(?string $filename): self
    {
        if ($filename !== null && ! $this->media->supportsFilename()) {
            throw InvalidMessageException::because('Only a document message carries a filename.');
        }

        $clone = clone $this;
        $clone->filename = $filename;

        return $clone;
    }

    public function type(): MessageType
    {
        return $this->media->toMessageType();
    }

    public function toPayload(): array
    {
        $media = array_filter([
            'id' => $this->id,
            'link' => $this->link,
            'caption' => $this->caption,
            'filename' => $this->filename,
        ], static fn (?string $value): bool => $value !== null);

        return [
            'type' => $this->media->value,
            $this->media->value => $media,
        ];
    }
}
