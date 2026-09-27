<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;
use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The documentation makes promises. These run them.
 *
 * Every sample published on the documentation site appears here with the output
 * it claims. The README is a front door now and carries no PHP, so this file is
 * what stops the published examples from drifting away from the code.
 */
it('produces the link the site opens with', function () {
    expect(WhatsappToolkit::to('333 123 4567')->html('<p>Hi <b>Sarah</b></p>')->url())
        ->toBe('https://wa.me/393331234567?text=Hi%20%2ASarah%2A');
});

it('produces the phone number forms the site lists', function () {
    $number = WhatsappToolkit::number('0039 333/123-4567');

    expect($number->waId())->toBe('393331234567')
        ->and($number->e164())->toBe('+393331234567')
        ->and($number->national())->toBe('3331234567')
        ->and(WhatsappToolkit::tryNumber('nonsense'))->toBeNull();
});

it('produces the formatting examples the site shows', function () {
    expect(WhatsappToolkit::format('<p>Ciao <b>Mario</b> &amp; soci</p>'))->toBe('Ciao *Mario* & soci')
        ->and(WhatsappToolkit::formatMarkdown('**Ciao** *Mario*'))->toBe('*Ciao* _Mario_')
        ->and(WhatsappToolkit::toHtml('*Ciao* _Mario_'))->toBe('<strong>Ciao</strong> <em>Mario</em>');
});

it('produces the upgrade page comparison', function () {
    // The headline fix: version 1 produced a link that opened nothing.
    expect(WhatsappToolkit::url('+39 333 123 4567', 'Ciao'))
        ->toBe('https://wa.me/393331234567?text=Ciao');

    config()->set('whatsapp-toolkit.link.target', LinkTarget::Api);

    expect(WhatsappToolkit::url('+39 333 123 4567', 'Ciao'))
        ->toBe('https://api.whatsapp.com/send?phone=393331234567&text=Ciao');
});

it('reuses one described chat across recipients', function () {
    config()->set('whatsapp-toolkit.templates.reminder', 'Il preventivo **:number** scade domani.');

    $reminder = WhatsappToolkit::chat()->template('reminder', ['number' => 'Q-7']);

    expect($reminder->to('333 123 4567')->url())->toContain('wa.me/393331234567')
        ->and($reminder->to('347 765 4321')->url())->toContain('wa.me/393477654321')
        ->and((string) $reminder->message())->toBe('Il preventivo *Q-7* scade domani.');
});

it('produces the markdown example the site prints', function () {
    $markdown = "# Titolo\n**Ciao** *mondo* ~~vecchio~~ `x` [Preventivo](https://novias.it/q/1)\n* uno\n+ due";

    expect(WhatsappToolkit::formatMarkdown($markdown))->toBe(
        "*Titolo*\n*Ciao* _mondo_ ~vecchio~ ```x``` Preventivo (https://novias.it/q/1)\n- uno\n- due",
    );
});

it('normalises every number in the published table', function (string $input, string $expected) {
    expect(WhatsappToolkit::number($input)->waId())->toBe($expected);
})->with([
    ['+39 333 123 4567', '393331234567'],
    ['(+39) 333/123-4567', '393331234567'],
    ['0039 333 1234567', '393331234567'],
    ['333 123 4567', '393331234567'],
    ['39 333 1234567', '393331234567'],
    ['039 2345678', '39392345678'],
    ['+1 (202) 555-0147', '12025550147'],
]);

it('offers the string macros the site lists', function () {
    expect(Str::whatsappNumber('333 123 4567'))->toBe('+393331234567')
        ->and(Str::whatsappUrl('333 123 4567', 'Ciao'))->toBe('https://wa.me/393331234567?text=Ciao')
        ->and((string) str('333 123 4567')->toWhatsappNumber())->toBe('+393331234567');
});

it('renders a template on its own as the site shows', function () {
    config()->set('whatsapp-toolkit.templates.quotation_expiring', 'Il preventivo **:number** scade il :date.');

    expect(WhatsappToolkit::renderTemplate('quotation_expiring', ['number' => 'Q-7', 'date' => '31/12']))
        ->toBe('Il preventivo **Q-7** scade il 31/12.');
});

it('exposes the message api the site documents', function () {
    $message = WhatsappToolkit::chat()->html('<p>Ciao <b>Mario</b></p>')->message();

    expect($message->length())->toBe(12)
        ->and($message->isEmpty())->toBeFalse()
        ->and($message->encoded())->toBe('Ciao%20%2AMario%2A')
        ->and((string) $message->truncate(8))->toBe('Ciao *M…');
});

it('validates a foreign number against an explicit country code', function () {
    $rule = ['phone' => [new Gabrielesbaiz\WhatsappToolkit\Rules\WhatsappNumber('44')]];

    expect(Validator::make(['phone' => '7700 900123'], $rule)->passes())->toBeTrue();
});

it('builds the shortcode link the site shows', function () {
    expect(WhatsappToolkit::chat()->shortCode('ABCDE12345')->text('Buongiorno')->url())
        ->toBe('https://wa.me/message/ABCDE12345?text=Buongiorno');
});

it('drops the preview flag when the site says it does', function () {
    $payload = Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TextMessage::make('https://novias.it')
        ->previewUrl(false)
        ->toPayload();

    expect($payload['text']['preview_url'])->toBeFalse();
});

it('accepts the blade attributes the site tabulates', function () {
    $markdown = Illuminate\Support\Facades\Blade::render('<x-whatsapp-link to="333 123 4567" message="**Ciao**" format="markdown" target="deep" />');

    expect($markdown)->toContain('href="whatsapp://send?phone=393331234567&amp;text=%2ACiao%2A"');

    $plain = Illuminate\Support\Facades\Blade::render('<x-whatsapp-link to="333 123 4567" message="*Ciao*" format="text" />');

    expect($plain)->toContain('text=%2ACiao%2A');
});
