<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;

it('converts markdown emphasis to WhatsApp emphasis', function (string $markdown, string $expected) {
    expect(WhatsappToolkit::formatMarkdown($markdown))->toBe($expected);
})->with([
    // The notations disagree on asterisks, which is the whole point of this
    // formatter: **bold** in Markdown is *bold* in WhatsApp.
    ['**Grassetto**', '*Grassetto*'],
    ['__Grassetto__', '*Grassetto*'],
    ['*Corsivo*', '_Corsivo_'],
    ['_Corsivo_', '_Corsivo_'],
    ['~~Barrato~~', '~Barrato~'],
    ['`codice`', '```codice```'],
    ['# Titolo', '*Titolo*'],
    ['### Titolo', '*Titolo*'],
]);

it('does not let the italic rule eat a converted bold', function () {
    expect(WhatsappToolkit::formatMarkdown('**Ciao** *mondo*'))->toBe('*Ciao* _mondo_');
});

it('keeps link destinations and drops image syntax', function () {
    expect(WhatsappToolkit::formatMarkdown('[Preventivo](https://novias.it/q/1)'))
        ->toBe('Preventivo (https://novias.it/q/1)');

    expect(WhatsappToolkit::formatMarkdown('![logo](https://novias.it/logo.png)'))
        ->toBe('https://novias.it/logo.png');
});

it('normalises bullet characters', function () {
    expect(WhatsappToolkit::formatMarkdown("* Uno\n+ Due\n- Tre"))->toBe("- Uno\n- Due\n- Tre");
});

it('returns an empty string for nothing', function () {
    expect(WhatsappToolkit::formatMarkdown(null))->toBe('')
        ->and(WhatsappToolkit::formatMarkdown('  '))->toBe('');
});
