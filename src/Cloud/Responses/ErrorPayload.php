<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Responses;

use Gabrielesbaiz\WhatsappToolkit\Enums\CloudErrorCode;

/**
 * Meta's error envelope, parsed.
 */
final readonly class ErrorPayload
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $message,
        public ?int $code = null,
        public ?int $subcode = null,
        public ?string $type = null,
        public ?string $details = null,
        public ?string $fbtraceId = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromArray(array $body): self
    {
        /** @var array<string, mixed> $error */
        $error = is_array($body['error'] ?? null) ? $body['error'] : $body;

        /** @var array<string, mixed> $data */
        $data = is_array($error['error_data'] ?? null) ? $error['error_data'] : [];

        return new self(
            message: (string) ($error['message'] ?? 'Unknown WhatsApp Cloud API error.'),
            code: isset($error['code']) ? (int) $error['code'] : null,
            subcode: isset($error['error_subcode']) ? (int) $error['error_subcode'] : null,
            type: isset($error['type']) ? (string) $error['type'] : null,
            details: isset($data['details']) ? (string) $data['details'] : null,
            fbtraceId: isset($error['fbtrace_id']) ? (string) $error['fbtrace_id'] : null,
            raw: $body,
        );
    }

    public function errorCode(): ?CloudErrorCode
    {
        return $this->code === null ? null : CloudErrorCode::tryFrom($this->code);
    }

    public function isRetryable(): bool
    {
        return $this->errorCode()?->isRetryable() ?? false;
    }

    /**
     * The message shown to a developer: Meta's text, the detail it hides in
     * error_data, and the hint that names the actual fix.
     */
    public function describe(): string
    {
        $parts = array_filter([
            $this->message,
            $this->details,
            $this->errorCode()?->hint(),
        ]);

        return implode(' ', $parts).($this->code !== null ? " (code {$this->code})" : '');
    }
}
