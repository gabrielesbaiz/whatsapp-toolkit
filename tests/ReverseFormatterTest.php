<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;

it('renders WhatsApp markup as html', function (string $text, string $expected) {
    expect(WhatsappToolkit::toHtml($text))->toBe($expected);
})->with([
    ['*Grassetto*', '<strong>Grassetto</strong>'],
    ['_Corsivo_', '<em>Corsivo</em>'],
    ['~Barrato~', '<del>Barrato</del>'],
    ['```codice```', '<code>codice</code>'],
]);

it('escapes the sender content before adding any markup', function () {
    // An inbound message is untrusted input; only the tags this formatter adds
    // may survive into the page.
    expect(WhatsappToolkit::toHtml('<script>alert(1)</script>'))
        ->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('renders quoted lines as blockquotes', function () {
    expect(WhatsappToolkit::toHtml('> Citazione'))->toBe('<blockquote>Citazione</blockquote>');
});

it('keeps newlines visible', function () {
    expect(WhatsappToolkit::toHtml("Uno\nDue"))->toBe("Uno<br>\nDue");
});

it('round trips through both formatters', function () {
    $html = '<p>Ciao <b>Mario</b>, tutto <i>bene</i>?</p>';

    expect(WhatsappToolkit::toHtml(WhatsappToolkit::format($html)))
        ->toBe('Ciao <strong>Mario</strong>, tutto <em>bene</em>?');
});
