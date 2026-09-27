<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Gabrielesbaiz\WhatsappToolkit\Contracts\QrRenderer;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\MissingDependencyException;

/**
 * A printable way into a conversation.
 *
 * Shops, invoices and business cards want a code rather than a link, and the
 * shortest link makes the sparsest code — which is why this always encodes the
 * wa.me form regardless of the configured default target.
 *
 * bacon/bacon-qr-code is suggested, not required: a package that only builds
 * links should not drag a rendering library into every application that
 * installs it.
 */
final class QrCode implements QrRenderer
{
    public function __construct(
        private readonly int $size = 300,
        private readonly int $margin = 2,
        private readonly string $errorCorrection = 'M',
    ) {}

    public function svg(string $payload, ?int $size = null): string
    {
        if (! $this->available()) {
            throw MissingDependencyException::qrCode();
        }

        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size ?? $this->size, $this->margin),
            new SvgImageBackEnd,
        ));

        return $writer->writeString($payload, 'utf-8', $this->level());
    }

    public function available(): bool
    {
        return class_exists(Writer::class);
    }

    private function level(): ErrorCorrectionLevel
    {
        $letter = match (strtoupper($this->errorCorrection)) {
            'L', 'Q', 'H' => strtoupper($this->errorCorrection),
            default => 'M',
        };

        /** @var ErrorCorrectionLevel */
        return forward_static_call([ErrorCorrectionLevel::class, $letter]);
    }
}
