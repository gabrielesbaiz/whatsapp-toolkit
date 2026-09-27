<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Responses;

/**
 * An uploaded or looked-up media object.
 *
 * Uploaded ids stop working after 30 days, and the url returned by a lookup is
 * short-lived and still needs the bearer token to fetch.
 */
final readonly class MediaResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $url = null,
        public ?string $mimeType = null,
        public ?string $sha256 = null,
        public ?int $fileSize = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(array $body): self
    {
        return new self(
            id: (string) ($body['id'] ?? ''),
            url: isset($body['url']) ? (string) $body['url'] : null,
            mimeType: isset($body['mime_type']) ? (string) $body['mime_type'] : null,
            sha256: isset($body['sha256']) ? (string) $body['sha256'] : null,
            fileSize: isset($body['file_size']) ? (int) $body['file_size'] : null,
            raw: $body,
        );
    }
}
