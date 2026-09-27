<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud\Messages;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\Concerns\HasRecipient;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MessageType;

final class LocationMessage implements CloudMessage
{
    use HasRecipient;

    private ?string $name = null;

    private ?string $address = null;

    private function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public static function make(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    public function name(?string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function address(?string $address): self
    {
        $clone = clone $this;
        $clone->address = $address;

        return $clone;
    }

    public function type(): MessageType
    {
        return MessageType::Location;
    }

    public function toPayload(): array
    {
        return [
            'type' => 'location',
            'location' => array_filter([
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'name' => $this->name,
                'address' => $this->address,
            ], static fn (mixed $value): bool => $value !== null),
        ];
    }
}
