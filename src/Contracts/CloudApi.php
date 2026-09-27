<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Contracts;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MediaResponse;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse;

interface CloudApi
{
    public function send(CloudMessage $message, ?string $to = null): MessageResponse;

    /**
     * @param  iterable<CloudMessage>  $messages
     * @return array<int, MessageResponse>
     */
    public function sendMany(iterable $messages): array;

    public function markRead(string $messageId): bool;

    public function uploadMedia(string $path, ?string $mimeType = null): MediaResponse;

    public function mediaUrl(string $mediaId): MediaResponse;

    public function downloadMedia(string $mediaId): string;

    /**
     * Escape hatch for endpoints this package does not model.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array;

    /** Send from a different business number than the configured one. */
    public function usingPhoneNumberId(string $id): static;
}
