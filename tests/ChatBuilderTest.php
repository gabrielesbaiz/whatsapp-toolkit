<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\ConfigurationException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\MessageTooLongException;
use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;

it('builds a wa.me link by default', function () {
    expect(WhatsappToolkit::to('+39 333 123 4567')->text('Ciao')->url())
        ->toBe('https://wa.me/393331234567?text=Ciao');
});

it('builds every link flavour', function (LinkTarget $target, string $expected) {
    expect(WhatsappToolkit::to('333 123 4567')->text('Ciao')->url($target))->toBe($expected);
})->with([
    [LinkTarget::WaMe, 'https://wa.me/393331234567?text=Ciao'],
    [LinkTarget::Api, 'https://api.whatsapp.com/send?phone=393331234567&text=Ciao'],
    [LinkTarget::Web, 'https://web.whatsapp.com/send?phone=393331234567&text=Ciao'],
    [LinkTarget::Deep, 'whatsapp://send?phone=393331234567&text=Ciao'],
]);

it('follows the configured default target', function () {
    config()->set('whatsapp-toolkit.link.target', LinkTarget::Api);

    expect(WhatsappToolkit::to('333 123 4567')->text('Ciao')->url())
        ->toStartWith('https://api.whatsapp.com/send');
});

it('addresses a business short link by code', function () {
    expect(WhatsappToolkit::chat()->shortCode('ABCDE12345')->text('Ciao')->url())
        ->toBe('https://wa.me/message/ABCDE12345?text=Ciao');
});

it('omits the text parameter when there is no body', function () {
    expect(WhatsappToolkit::to('333 123 4567')->url())->toBe('https://wa.me/393331234567');
});

it('encodes spaces as %20 rather than plus', function () {
    // urlencode() writes "+", which some clients render literally in the
    // prefilled body.
    expect(WhatsappToolkit::to('333 123 4567')->text('Ciao Mario')->url())
        ->toContain('text=Ciao%20Mario');
});

it('encodes newlines and markers produced by the formatter', function () {
    $url = WhatsappToolkit::to('333 123 4567')->html('<p>Ciao <b>Mario</b></p><p>Grazie</p>')->url();

    expect($url)->toBe('https://wa.me/393331234567?text=Ciao%20%2AMario%2A%0A%0AGrazie');
});

it('accepts each source notation', function () {
    expect((string) WhatsappToolkit::chat()->html('<b>x</b>')->message())->toBe('*x*')
        ->and((string) WhatsappToolkit::chat()->markdown('**x**')->message())->toBe('*x*')
        ->and((string) WhatsappToolkit::chat()->text('*x*')->message())->toBe('*x*');
});

it('fills a named template', function () {
    config()->set('whatsapp-toolkit.templates.followup', 'Buongiorno :name, il preventivo :number è pronto.');

    expect((string) WhatsappToolkit::chat()->template('followup', ['name' => 'Mario', 'number' => 'Q-7'])->message())
        ->toBe('Buongiorno Mario, il preventivo Q-7 è pronto.');
});

it('replaces the longest placeholder first', function () {
    config()->set('whatsapp-toolkit.templates.t', ':name :name_full');

    expect((string) WhatsappToolkit::chat()->template('t', ['name' => 'Mario', 'name_full' => 'Mario Rossi'])->message())
        ->toBe('Mario Mario Rossi');
});

it('complains about a template that does not exist', function () {
    expect(fn () => WhatsappToolkit::chat()->template('nope')->message())
        ->toThrow(ConfigurationException::class);
});

it('truncates an over-long body at a word boundary', function () {
    $body = str_repeat('parola ', 50);

    $message = WhatsappToolkit::chat()->text($body)->truncate(40)->message();

    expect($message->length())->toBeLessThanOrEqual(40)
        ->and((string) $message)->toEndWith('…')
        ->and((string) $message)->not->toContain('parol…');
});

it('refuses an over-long body when configured to throw', function () {
    config()->set('whatsapp-toolkit.link.max_length', 20);
    config()->set('whatsapp-toolkit.link.on_overflow', 'throw');

    expect(fn () => WhatsappToolkit::chat()->text(str_repeat('a', 50))->message())
        ->toThrow(MessageTooLongException::class);
});

it('lets an over-long body through when configured to ignore', function () {
    config()->set('whatsapp-toolkit.link.max_length', 20);
    config()->set('whatsapp-toolkit.link.on_overflow', 'ignore');

    expect(WhatsappToolkit::chat()->text(str_repeat('a', 50))->message()->length())->toBe(50);
});

it('never mutates the chat it was built from', function () {
    $base = WhatsappToolkit::chat()->text('Base');

    $other = $base->to('333 123 4567')->text('Altro');

    expect((string) $base->message())->toBe('Base')
        ->and($base->recipient())->toBeNull()
        ->and((string) $other->message())->toBe('Altro');
});

it('renders an escaped anchor', function () {
    $html = (string) WhatsappToolkit::to('333 123 4567')->text('Ciao & grazie')->link('Chatta');

    // The & between query parameters must not reach the page raw.
    expect($html)->toContain('rel="noopener noreferrer"')
        ->and($html)->toContain('>Chatta</a>')
        ->and($html)->not->toMatch('/&(?!amp;|#)/');
});

it('casts to its own url', function () {
    expect((string) WhatsappToolkit::to('333 123 4567')->text('Ciao'))
        ->toBe('https://wa.me/393331234567?text=Ciao');
});

it('describes itself as an array', function () {
    expect(WhatsappToolkit::to('333 123 4567')->html('<b>Ciao</b>')->toArray())
        ->toBe([
            'to' => '+393331234567',
            'text' => '*Ciao*',
            'url' => 'https://wa.me/393331234567?text=%2ACiao%2A',
        ]);
});

it('offers a one-line url helper', function () {
    expect(WhatsappToolkit::url('333 123 4567', '<b>Ciao</b>'))
        ->toBe('https://wa.me/393331234567?text=%2ACiao%2A');
});
