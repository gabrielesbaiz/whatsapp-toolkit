<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\InteractiveButtonsMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\MediaMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\ReactionMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TemplateMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TextMessage;
use Gabrielesbaiz\WhatsappToolkit\Enums\MediaType;
use Gabrielesbaiz\WhatsappToolkit\Events\WhatsappMessageSent;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\AuthenticationException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\ConfigurationException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidMessageException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\RateLimitException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\ReEngagementRequiredException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\TemplateException;
use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => enableCloud());

it('stays dormant until it is switched on', function () {
    config()->set('whatsapp-toolkit.cloud.enabled', false);

    expect(fn () => WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567')))
        ->toThrow(ConfigurationException::class);
});

it('posts a text message to the messages endpoint', function () {
    fakeGraph(graphAccepted());

    $response = WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('+39 333 123 4567'));

    expect($response->id)->toBe('wamid.TEST')
        ->and($response->recipientId)->toBe('393331234567');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://graph.facebook.com/v21.0/1234567890/messages'
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '393331234567'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Ciao'
            && $request->hasHeader('Authorization', 'Bearer test-token');
    });
});

it('sends what a chat builder describes', function () {
    fakeGraph(graphAccepted());

    WhatsappToolkit::to('333 123 4567')->html('<b>Ciao</b>')->send();

    Http::assertSent(fn ($request) => $request['text']['body'] === '*Ciao*');
});

it('sends from another business number on request', function () {
    fakeGraph(graphAccepted());

    WhatsappToolkit::cloud()->usingPhoneNumberId('5550001')->send(TextMessage::make('Ciao')->to('393331234567'));

    Http::assertSent(fn ($request) => str_contains($request->url(), '/5550001/messages'));
});

it('builds the payload for every message type', function () {
    fakeGraph(graphAccepted());

    $api = WhatsappToolkit::cloud();

    $api->send(TemplateMessage::make('order_shipped', 'it')->body('Q-7', 3)->to('393331234567'));
    Http::assertSent(fn ($r) => ($r['template']['name'] ?? null) === 'order_shipped'
        && ($r['template']['language']['code'] ?? null) === 'it'
        && ($r['template']['components'][0]['parameters'][1]['text'] ?? null) === '3');

    $api->send(MediaMessage::id(MediaType::Document, 'media-1')->filename('preventivo.pdf')->to('393331234567'));
    Http::assertSent(fn ($r) => $r['type'] === 'document' && ($r['document']['filename'] ?? null) === 'preventivo.pdf');

    $api->send(InteractiveButtonsMessage::make('Confermi?')->button('yes', 'Sì')->button('no', 'No')->to('393331234567'));
    Http::assertSent(fn ($r) => ($r['interactive']['action']['buttons'][0]['reply']['id'] ?? null) === 'yes');

    $api->send(ReactionMessage::make('wamid.X', '👍')->to('393331234567'));
    Http::assertSent(fn ($r) => ($r['reaction']['emoji'] ?? null) === '👍');
});

it('validates a message while it is being built', function () {
    expect(fn () => TextMessage::make(''))->toThrow(InvalidMessageException::class)
        ->and(fn () => TextMessage::make(str_repeat('a', 4097)))->toThrow(InvalidMessageException::class)
        ->and(fn () => InteractiveButtonsMessage::make('x')->button('1', 'a')->button('2', 'b')->button('3', 'c')->button('4', 'd'))
        ->toThrow(InvalidMessageException::class)
        ->and(fn () => MediaMessage::link(MediaType::Image, 'http://insecure.test/a.png'))
        ->toThrow(InvalidMessageException::class)
        ->and(fn () => MediaMessage::id(MediaType::Sticker, 'x')->caption('nope'))
        ->toThrow(InvalidMessageException::class);
});

it('maps meta error codes to exceptions that name the fix', function (int $code, string $exception) {
    fakeGraph(graphError($code), 400);

    expect(fn () => WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567')))
        ->toThrow($exception);
})->with([
    [190, AuthenticationException::class],
    [131047, ReEngagementRequiredException::class],
    [132001, TemplateException::class],
    [130429, RateLimitException::class],
]);

it('explains the 24 hour window rather than repeating meta wording', function () {
    fakeGraph(graphError(131047, 'Re-engagement message'), 400);

    expect(fn () => WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567')))
        ->toThrow(ReEngagementRequiredException::class, 'Outside the 24-hour customer service window');
});

it('carries the fbtrace id meta support asks for', function () {
    fakeGraph(graphError(190), 401);

    try {
        WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567'));
    } catch (AuthenticationException $e) {
        expect($e->fbtraceId())->toBe('Az1234')
            ->and($e->isRetryable())->toBeFalse();
    }
});

it('does not retry a failure that will repeat itself', function () {
    config()->set('whatsapp-toolkit.cloud.retry.times', 3);
    fakeGraph(graphError(190), 401);

    expect(fn () => WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567')))
        ->toThrow(AuthenticationException::class);

    Http::assertSentCount(1);
});

it('retries a transient failure', function () {
    config()->set('whatsapp-toolkit.cloud.retry.times', 3);
    config()->set('whatsapp-toolkit.cloud.retry.sleep', 1);

    Http::fake(['graph.facebook.com/*' => Http::sequence()
        ->push(graphError(2), 500)
        ->push(graphAccepted()),
    ]);

    expect(WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567'))->id)->toBe('wamid.TEST');

    Http::assertSentCount(2);
});

it('reports a rate limit with the delay meta asked for', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(graphError(130429), 429, ['Retry-After' => '30'])]);

    try {
        WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567'));
    } catch (RateLimitException $e) {
        expect($e->retryAfter())->toBe(30);
    }
});

it('marks a message as read only when asked', function () {
    fakeGraph(['success' => true]);

    expect(WhatsappToolkit::cloud()->markRead('wamid.X'))->toBeTrue();

    Http::assertSent(fn ($r) => $r['status'] === 'read' && $r['message_id'] === 'wamid.X');
});

it('announces every message it sends', function () {
    Event::fake([WhatsappMessageSent::class]);
    fakeGraph(graphAccepted());

    WhatsappToolkit::cloud()->send(TextMessage::make('Ciao')->to('393331234567'));

    Event::assertDispatched(WhatsappMessageSent::class);
});
