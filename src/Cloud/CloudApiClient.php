<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MediaResponse;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudApi;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappMessageSent;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\ConfigurationException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * The Meta Cloud API, as much of it as sending a message needs.
 *
 * The HTTP factory is injected rather than reached for through the facade, so
 * an application's Http::fake() intercepts these calls and the class itself can
 * be constructed in a unit test without a container.
 */
final class CloudApiClient implements CloudApi
{
    private ?string $phoneNumberId = null;

    public function __construct(
        private readonly Factory $http,
        private readonly Repository $config,
        private readonly Endpoint $endpoint,
        private readonly ErrorMapper $mapper,
        private readonly Dispatcher $events,
    ) {}

    public function send(CloudMessage $message, ?string $to = null): MessageResponse
    {
        $recipient = $to ?? $message->recipient();

        if ($recipient === null || $recipient === '') {
            throw InvalidPhoneNumberException::empty();
        }

        $payload = array_merge([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
        ], $message->toPayload());

        $response = MessageResponse::fromArray(
            $this->request()->post($this->endpoint->messages($this->phoneNumberId), $payload)
                ->throw(fn (Response $r) => throw $this->mapper->fromResponse($r))
                ->json() ?? [],
        );

        $this->events->dispatch(new WhatsappMessageSent($message, $response));

        return $response;
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
        $response = $this->request()->post($this->endpoint->messages($this->phoneNumberId), [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ]);

        return $response->successful();
    }

    public function uploadMedia(string $path, ?string $mimeType = null): MediaResponse
    {
        if (! is_readable($path)) {
            throw ConfigurationException::missing("cloud.media [{$path}] is not readable.");
        }

        $response = $this->request()
            ->attach('file', (string) file_get_contents($path), basename($path), array_filter([
                'Content-Type' => $mimeType,
            ]))
            ->post($this->endpoint->media($this->phoneNumberId), [
                'messaging_product' => 'whatsapp',
            ])
            ->throw(fn (Response $r) => throw $this->mapper->fromResponse($r));

        return MediaResponse::fromArray((array) $response->json());
    }

    public function mediaUrl(string $mediaId): MediaResponse
    {
        $response = $this->request()->get($this->endpoint->object($mediaId))
            ->throw(fn (Response $r) => throw $this->mapper->fromResponse($r));

        return MediaResponse::fromArray((array) $response->json());
    }

    public function downloadMedia(string $mediaId): string
    {
        $media = $this->mediaUrl($mediaId);

        if ($media->url === null) {
            throw ConfigurationException::missing("cloud.media [{$mediaId}] returned no url.");
        }

        // The CDN URL is short-lived and still expects the bearer token.
        return $this->request()->get($media->url)
            ->throw(fn (Response $r) => throw $this->mapper->fromResponse($r))
            ->body();
    }

    public function post(string $endpoint, array $payload): array
    {
        $url = str_starts_with($endpoint, 'http')
            ? $endpoint
            : $this->endpoint->forNumber(ltrim($endpoint, '/'), $this->phoneNumberId);

        return (array) $this->request()->post($url, $payload)
            ->throw(fn (Response $r) => throw $this->mapper->fromResponse($r))
            ->json();
    }

    public function usingPhoneNumberId(string $id): static
    {
        $clone = clone $this;
        $clone->phoneNumberId = $id;

        return $clone;
    }

    /**
     * A request already carrying credentials, timeouts and the retry policy.
     */
    private function request(): PendingRequest
    {
        if (! $this->config->get('whatsapp-toolkit.cloud.enabled')) {
            throw ConfigurationException::cloudDisabled();
        }

        $token = (string) $this->config->get('whatsapp-toolkit.cloud.access_token');

        if ($token === '') {
            throw ConfigurationException::missing('cloud.access_token');
        }

        $base = (int) $this->config->get('whatsapp-toolkit.cloud.retry.sleep', 200);
        $max = (int) $this->config->get('whatsapp-toolkit.cloud.retry.max', 5000);

        return $this->http
            ->withToken($token)
            ->timeout((int) $this->config->get('whatsapp-toolkit.cloud.timeout', 10))
            ->connectTimeout((int) $this->config->get('whatsapp-toolkit.cloud.connect_timeout', 5))
            ->acceptJson()
            ->retry(
                times: max(1, (int) $this->config->get('whatsapp-toolkit.cloud.retry.times', 3)),
                sleepMilliseconds: static function (int $attempt, Throwable $e) use ($base, $max): int {
                    // Honour Meta's own Retry-After when it sends one; fall back
                    // to exponential backoff with a little jitter so a queue
                    // worker fleet does not resynchronise on every failure.
                    $after = $e instanceof RequestException ? $e->response->header('Retry-After') : '';

                    if ($after !== '') {
                        return min((int) $after * 1000, $max);
                    }

                    return min($base * (2 ** ($attempt - 1)), $max) + random_int(0, 100);
                },
                when: fn (Throwable $e): bool => $this->mapper->isRetryable($e),
                throw: false,
            );
    }
}
