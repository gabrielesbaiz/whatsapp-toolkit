<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;

it('renders the chat link as an svg', function () {
    $svg = (string) WhatsappToolkit::to('333 123 4567')->text('Ciao')->qr();

    expect($svg)->toStartWith('<?xml')->and($svg)->toContain('<svg');
});

it('always encodes the short link, whatever the configured target', function () {
    // The shortest URL makes the sparsest code, which is what survives being
    // printed and photographed.
    config()->set('whatsapp-toolkit.link.target', Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget::Api);

    $svgShort = (string) WhatsappToolkit::to('333 123 4567')->text('Ciao')->qr();
    $svgLong = (string) WhatsappToolkit::to('333 123 4567')->text('Ciao')->qr();

    expect($svgShort)->toBe($svgLong);
});

it('offers the code as a data uri', function () {
    expect(WhatsappToolkit::to('333 123 4567')->qrDataUri())
        ->toStartWith('data:image/svg+xml;base64,');
});

it('honours a size override', function () {
    expect((string) WhatsappToolkit::to('333 123 4567')->qr(120))->toContain('width="120"');
});
