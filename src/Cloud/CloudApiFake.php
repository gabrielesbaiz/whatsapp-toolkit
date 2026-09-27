<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud;

use Closure;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MediaResponse;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudApi;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * A Cloud API that records instead of sending.
 *
 * Installed by WhatsappToolkit::fake(). Assertions are phrased in terms of
 * message objects rather than HTTP requests, which is what a test about
 * business behaviour actually wants to say. The package's own tests of request
 * building use Http::fake() instead, because something has to exercise the
 * wire format.
 */
final class CloudApiFake implements CloudApi
{
    /** @var array<int, array{message: CloudMessage, to: string}> */
    private array $sent = [];

    /** @var array<int, string> */
    private array $read = [];

    /** @var array<int, string> */
    private array $uploaded = [];

    /** @var array<int, MessageResponse|Throwable> */
    private array $queued = [];

    private int $counter = 0;

    /**
     * Queue the next result, whether a response or a failure.
     */
    public function push(MessageResponse|Throwable $result): self
    {
        $this->queued[] = $result;

        return $this;
    }

    public function send(CloudMessage $message, ?string $to = null): MessageResponse
    {
        $recipient = $to ?? $message->recipient() ?? '';

        $this->sent[] = ['message' => $message, 'to' => $recipient];

        $next = array_shift($this->queued);

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next ?? new MessageResponse(
            id: 'wamid.fake.'.(++$this->counter),
            recipientId: $recipient,
            input: $recipient,
        );
    }

    public function sendMany(iterable $messages): array
    {
        $responses = [];

        foreach ($messages as $message) {
            $responses[] = $this->send($message);
        }

        return $responses;
    }

    public function markRead(string $messageId): bool
    {
        $this->read[] = $messageId;

        return true;
    }

    public function uploadMedia(string $path, ?string $mimeType = null): MediaResponse
    {
        $this->uploaded[] = $path;

        return new MediaResponse(id: 'media.fake.'.count($this->uploaded), mimeType: $mimeType);
    }

    public function mediaUrl(string $mediaId): MediaResponse
    {
        return new MediaResponse(id: $mediaId, url: "https://example.test/{$mediaId}");
    }

    public function downloadMedia(string $mediaId): string
    {
        return '';
    }

    public function post(string $endpoint, array $payload): array
    {
        return [];
    }

    public function usingPhoneNumberId(string $id): static
    {
        return $this;
    }

    /**
     * Everything recorded, optionally narrowed to one message class.
     *
     * @param  class-string<CloudMessage>|Closure(CloudMessage, string): bool|null  $type
     * @return Collection<int, CloudMessage>
     */
    public function sent(string|Closure|null $type = null): Collection
    {
        return (new Collection($this->sent))
            ->filter(fn (array $entry): bool => $this->matches($entry, $type))
            ->map(static fn (array $entry): CloudMessage => $entry['message'])
            ->values();
    }

    /**
     * @param  class-string<CloudMessage>|Closure(CloudMessage, string): bool  $type
     * @param  Closure(CloudMessage, string): bool|null  $callback
     */
    public function assertSent(string|Closure $type, ?Closure $callback = null): void
    {
        $matches = (new Collection($this->sent))
            ->filter(fn (array $entry): bool => $this->matches($entry, $type))
            ->filter(fn (array $entry): bool => $callback === null || $callback($entry['message'], $entry['to']));

        Assert::assertTrue(
            $matches->isNotEmpty(),
            'No matching WhatsApp message was sent.',
        );
    }

    /**
     * @param  class-string<CloudMessage>|Closure(CloudMessage, string): bool  $type
     */
    public function assertNotSent(string|Closure $type): void
    {
        Assert::assertTrue(
            (new Collection($this->sent))->filter(fn (array $e): bool => $this->matches($e, $type))->isEmpty(),
            'An unexpected WhatsApp message was sent.',
        );
    }

    /**
     * The recipient may be written any way at all; it is normalized first, so a
     * test can assert against the number a human would type.
     */
    public function assertSentTo(string $recipient, string|Closure|null $type = null): void
    {
        $expected = PhoneNumber::tryParse($recipient)?->waId() ?? ltrim($recipient, '+');

        $matches = (new Collection($this->sent))
            ->filter(fn (array $entry): bool => $this->matches($entry, $type))
            ->filter(static fn (array $entry): bool => $entry['to'] === $expected);

        Assert::assertTrue($matches->isNotEmpty(), "No WhatsApp message was sent to [{$recipient}].");
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->sent);
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->sent, 'WhatsApp messages were sent when none were expected.');
    }

    public function assertMarkedRead(string $messageId): void
    {
        Assert::assertContains($messageId, $this->read);
    }

    public function assertMediaUploaded(?string $path = null): void
    {
        $path === null
            ? Assert::assertNotEmpty($this->uploaded)
            : Assert::assertContains($path, $this->uploaded);
    }

    /**
     * @param  array{message: CloudMessage, to: string}  $entry
     * @param  class-string<CloudMessage>|Closure(CloudMessage, string): bool|null  $type
     */
    private function matches(array $entry, string|Closure|null $type): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof Closure) {
            return $type($entry['message'], $entry['to']);
        }

        return $entry['message'] instanceof $type;
    }
}
