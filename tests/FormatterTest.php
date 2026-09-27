<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;
use Gabrielesbaiz\WhatsappToolkit\Formatters\HtmlFormatter;
use Gabrielesbaiz\WhatsappToolkit\Support\Memo;

it('returns an empty string for nothing to format', function (?string $input) {
    expect(WhatsappToolkit::format($input))->toBe('');
})->with([null, '', '   ']);

it('leaves plain text alone', function () {
    expect(WhatsappToolkit::format('  Buongiorno Mario  '))->toBe('Buongiorno Mario');
});

it('converts inline formatting to WhatsApp markers', function (string $html, string $expected) {
    expect(WhatsappToolkit::format($html))->toBe($expected);
})->with([
    ['<b>Bold</b>', '*Bold*'],
    ['<strong>Bold</strong>', '*Bold*'],
    ['<i>Italic</i>', '_Italic_'],
    ['<em>Italic</em>', '_Italic_'],
    ['<s>Gone</s>', '~Gone~'],
    ['<del>Gone</del>', '~Gone~'],
    ['<code>x = 1</code>', '```x = 1```'],
]);

it('hugs the text with markers instead of padding them', function () {
    // Version 1 emitted " *Bold*  text", which rendered with stray spaces.
    expect(WhatsappToolkit::format('<b>Bold</b> text'))->toBe('*Bold* text');
});

it('keeps the space a trimmed span sat against', function () {
    // The whitespace inside the tag used to be trimmed away entirely, so the
    // marker welded to the next word: "commodo._Non".
    expect(WhatsappToolkit::format('<p>fine <em>frase. </em>Altra frase</p>'))
        ->toBe('fine _frase._ Altra frase');
});

it('widens a marker that would land inside a word', function (string $html, string $expected) {
    // WhatsApp prints a marker with a letter on its outer side verbatim, so an
    // editor selection that cut into a word has to grow to cover the word.
    expect(WhatsappToolkit::format($html))->toBe($expected);
})->with([
    ['C<strong>ommodo cill</strong>um', '*Commodo cillum*'],
    ['te<em>mpor non</em>', '_tempor non_'],
    ['ullamc<del>o velit</del>', '~ullamco velit~'],
    ['par<em>ziale</em>mente', '_parzialemente_'],
]);

it('leaves a marker already on a word boundary where it is', function (string $html, string $expected) {
    expect(WhatsappToolkit::format($html))->toBe($expected);
})->with([
    ['Ciao <strong>Mario</strong>, tutto bene?', 'Ciao *Mario*, tutto bene?'],
    ['<p>(<em>forse</em>)</p>', '(_forse_)'],
    ['<p><strong>Tutto</strong></p>', '*Tutto*'],
]);

it('turns line breaks and paragraphs into newlines', function () {
    expect(WhatsappToolkit::format('Uno<br>Due<br/>Tre<br />Quattro'))
        ->toBe("Uno\nDue\nTre\nQuattro");

    expect(WhatsappToolkit::format('<p>Uno</p><p>Due</p>'))->toBe("Uno\n\nDue");
});

it('decodes html entities instead of leaking them into the chat', function () {
    expect(WhatsappToolkit::format('<p>Caff&egrave; &amp; cornetto &#39;caldo&#39;</p>'))
        ->toBe("Caffè & cornetto 'caldo'");

    expect(WhatsappToolkit::format('Spazio&nbsp;unificatore'))->toBe('Spazio unificatore');
});

it('numbers each ordered list from one', function () {
    // The counter used to be shared across the whole message, so a second list
    // carried on from where the first stopped.
    expect(WhatsappToolkit::format('<ol><li>A</li><li>B</li></ol><ol><li>X</li></ol>'))
        ->toBe("1. A\n2. B\n\n1. X");
});

it('renders unordered lists with dashes', function () {
    expect(WhatsappToolkit::format('<ul><li>Uno</li><li>Due</li></ul>'))->toBe("- Uno\n- Due");
});

it('prefixes every line of a blockquote', function () {
    expect(WhatsappToolkit::format('<blockquote><p>Citazione</p></blockquote>'))->toBe('> Citazione');
});

it('keeps the destination of a link', function () {
    // strip_tags() alone turns "click here" into a dead end.
    expect(WhatsappToolkit::format('Vai <a href="https://novias.it">qui</a> ora'))
        ->toBe('Vai qui (https://novias.it) ora');
});

it('does not repeat a url that is its own label', function () {
    expect(WhatsappToolkit::format('<a href="https://novias.it">https://novias.it</a>'))
        ->toBe('https://novias.it');
});

it('drops the destination when configured to', function () {
    $formatter = new HtmlFormatter(new Memo, 'strip');

    expect($formatter->format('<a href="https://novias.it">qui</a>'))->toBe('qui');
});

it('preserves indentation inside a preformatted block', function () {
    expect(WhatsappToolkit::format("<pre>if (x) {\n    y();\n}</pre>"))
        ->toBe("```if (x) {\n    y();\n}```");
});

it('does not format markup that was escaped in the source', function () {
    expect(WhatsappToolkit::format('&lt;b&gt;not bold&lt;/b&gt;'))->toBe('<b>not bold</b>');
});

it('strips tags it has no WhatsApp equivalent for', function () {
    expect(WhatsappToolkit::format('<table><tr><td>Cella</td></tr></table>'))->toBe('Cella');
});

it('returns plain text rather than something url encoded', function () {
    // Formatting and encoding are separate steps in v2; fusing them is what
    // made the v1 output unusable for anything but a query string.
    expect(WhatsappToolkit::format('<p>Ciao mondo!</p>'))->toBe('Ciao mondo!');
});

it('memoizes repeated bodies', function () {
    $memo = new Memo;
    $formatter = new HtmlFormatter($memo);

    $formatter->format('<p>Uguale</p>');
    $formatter->format('<p>Uguale</p>');

    expect($memo->count())->toBe(1);
});

it('skips the memo entirely when it is disabled', function () {
    $memo = new Memo(128, false);

    (new HtmlFormatter($memo))->format('<p>Uguale</p>');

    expect($memo->count())->toBe(0);
});

it('keeps the memo bounded', function () {
    $memo = new Memo(4);
    $formatter = new HtmlFormatter($memo);

    foreach (range(1, 20) as $i) {
        $formatter->format("<p>Corpo {$i}</p>");
    }

    expect($memo->count())->toBe(4);
});
