<?php

use Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit;

it('formats phone number correctly using formatPhoneNumber', function () {
    expect(WhatsappToolkit::formatPhoneNumber('+39 1234567890'))
        ->toBe('%2B39+1234567890');
});

it('returns empty string when formatPhoneNumber input is null', function () {
    expect(WhatsappToolkit::formatPhoneNumber(null))->toBe('');
});

it('returns empty string when formatMessage input is null', function () {
    expect(WhatsappToolkit::formatMessage(null))->toBe('');
});

it('replaces &nbsp; with space in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('Hello&nbsp;World'))
        ->toBe(urlencode('Hello World'));
});

it('replaces <br> tags with newline in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('Line1<br>Line2<br/>Line3<br />Line4'))
        ->toBe(urlencode("Line1\nLine2\nLine3\nLine4"));
});

it('formats bold text with asterisks in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<b>Bold</b> text and <strong>Strong</strong> text'))
        ->toBe(urlencode('*Bold*  text and  *Strong*  text'));
});

it('formats italic text with underscores in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<i>Italic</i> text and <em>Emphasized</em> text'))
        ->toBe(urlencode('_Italic_  text and  _Emphasized_  text'));
});

it('formats strikethrough text with tildes in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<s>Strikethrough</s> text and <del>Deleted</del> text'))
        ->toBe(urlencode('~Strikethrough~  text and  ~Deleted~  text'));
});

it('formats preformatted text with triple backticks in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<pre>Code block</pre>'))
        ->toBe(urlencode('```Code block```'));
});

it('formats blockquote properly in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<blockquote><p>Quoted text</p></blockquote>'))
        ->toBe(urlencode('> Quoted text'));
});

it('formats unordered lists correctly in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<ul><li>Item 1</li><li>Item 2</li></ul>'))
        ->toBe(urlencode("- Item 1\n- Item 2"));
});

it('formats ordered lists correctly in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<ol><li>First</li><li>Second</li></ol>'))
        ->toBe(urlencode("1. First\n2. Second"));
});

it('removes excess whitespace from formatted message in formatMessage', function () {
    expect(WhatsappToolkit::formatMessage('<p>   Hello   </p>'))
        ->toBe(urlencode('Hello'));
});

it('returns WhatsApp URL with formatted phone number and message', function () {
    expect(WhatsappToolkit::url('+39 1234567890', 'Hello World'))
        ->toBe('https://api.whatsapp.com/send?phone=%2B39+1234567890&text=Hello+World');
});

it('returns WhatsApp URL with empty message when message is null', function () {
    expect(WhatsappToolkit::url('+39 1234567890', null))
        ->toBe('https://api.whatsapp.com/send?phone=%2B39+1234567890&text=');
});

it('returns WhatsApp URL with empty phone number when phone number is null', function () {
    expect(WhatsappToolkit::url(null, 'Hello World'))
        ->toBe('https://api.whatsapp.com/send?phone=&text=Hello+World');
});

it('returns WhatsApp URL with empty phone number and empty message when both are null', function () {
    expect(WhatsappToolkit::url(null, null))
        ->toBe('https://api.whatsapp.com/send?phone=&text=');
});

it('formats phone number correctly within WhatsApp URL', function () {
    expect(WhatsappToolkit::url(' 123 456 7890 ', 'Test'))
        ->toBe('https://api.whatsapp.com/send?phone=+123+456+7890+&text=Test');
});

it('formats message with special characters correctly within WhatsApp URL', function () {
    expect(WhatsappToolkit::url('+39 1234567890', '<b>Bold</b> & <i>Italic</i>'))
        ->toBe('https://api.whatsapp.com/send?phone=%2B39+1234567890&text=%2ABold%2A++%26++_Italic_');
});

it('encodes newlines and HTML elements in message correctly within WhatsApp URL', function () {
    expect(WhatsappToolkit::url('+39 1234567890', 'Line1<br>Line2'))
        ->toBe('https://api.whatsapp.com/send?phone=%2B39+1234567890&text=Line1%0ALine2');
});
