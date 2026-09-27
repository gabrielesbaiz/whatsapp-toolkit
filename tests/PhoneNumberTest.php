<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException;
use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;

it('reduces a number to the digits WhatsApp dials', function (string $input, string $expected) {
    expect(WhatsappToolkit::number($input)->waId())->toBe($expected);
})->with([
    ['+39 333 123 4567', '393331234567'],
    ['+39333123456 7', '393331234567'],
    ['(+39) 333/123-4567', '393331234567'],
    ['0039 333 1234567', '393331234567'],
    ['333 123 4567', '393331234567'],
    ['393331234567', '393331234567'],
    ['+1 (202) 555-0147', '12025550147'],
]);

it('keeps a national trunk zero out of the international form', function () {
    // 039 is an Italian area code, not a country code with a stray zero.
    expect(WhatsappToolkit::number('039 2345678')->waId())->toBe('39392345678');
});

it('exposes e164 and national forms', function () {
    $number = WhatsappToolkit::number('333 123 4567');

    expect($number->e164())->toBe('+393331234567')
        ->and($number->national())->toBe('3331234567')
        ->and((string) $number)->toBe('+393331234567');
});

it('never re-derives a wa_id handed back by Meta', function () {
    expect(PhoneNumber::fromWaId('5491123456789')->waId())->toBe('5491123456789');
});

it('refuses a number it cannot use', function (?string $input) {
    expect(fn () => WhatsappToolkit::number($input))->toThrow(InvalidPhoneNumberException::class);
})->with([null, '', '   ', 'not a phone', '12', '1234567890123456789']);

it('returns null instead of throwing when asked to try', function () {
    expect(WhatsappToolkit::tryNumber('nonsense'))->toBeNull()
        ->and(WhatsappToolkit::tryNumber('+39 333 1234567'))->not->toBeNull();
});

it('refuses a local number when no default country code is configured', function () {
    config()->set('whatsapp-toolkit.default_country_code', null);
    PhoneNumber::$defaultCountryCode = null;

    expect(fn () => PhoneNumber::parse('3331234567'))->toThrow(InvalidPhoneNumberException::class);
});
