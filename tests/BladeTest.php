<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders a click-to-chat anchor', function () {
    $html = Blade::render('<x-whatsapp-link :to="$to" :message="$msg">Chatta</x-whatsapp-link>', [
        'to' => '333 123 4567',
        'msg' => '<b>Ciao</b>',
    ]);

    expect($html)->toContain('href="https://wa.me/393331234567?text=%2ACiao%2A"')
        ->and($html)->toContain('target="_blank"')
        ->and($html)->toContain('>Chatta</a>');
});

it('escapes the ampersand between query parameters', function () {
    // Two hand-written anchors in the application this package was extracted
    // from emitted a raw & straight into the markup.
    $html = Blade::render('<x-whatsapp-link to="333 123 4567" message="Ciao" target="api" />');

    expect($html)->toContain('&amp;text=')
        ->and($html)->not->toMatch('/&(?!amp;|quot;|#)/');
});

it('renders nothing when the number is unusable', function () {
    expect(trim(Blade::render('<x-whatsapp-link to="nonsense">Chatta</x-whatsapp-link>')))->toBe('');
});

it('passes extra attributes through', function () {
    $html = Blade::render('<x-whatsapp-link to="333 123 4567" class="btn" />');

    expect($html)->toContain('class="btn"');
});

it('accepts markdown and plain bodies', function () {
    expect(Blade::render('<x-whatsapp-link to="333 123 4567" message="**Ciao**" format="markdown" />'))
        ->toContain('text=%2ACiao%2A');
});

it('exposes a blade directive for the bare url', function () {
    expect(trim(Blade::render('@whatsappUrl("333 123 4567", "Ciao")')))
        ->toBe('https://wa.me/393331234567?text=Ciao');
});
