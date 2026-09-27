<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TemplateMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TextMessage;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\RateLimitException;
use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;
use Illuminate\Support\Facades\Http;

it('records instead of sending', function () {
    $fake = WhatsappToolkit::fake();
    Http::fake();

    WhatsappToolkit::to('333 123 4567')->text('Ciao')->send();

    $fake->assertSentCount(1);
    $fake->assertSent(TextMessage::class);
    $fake->assertSentTo('+39 333 123 4567');
    $fake->assertNotSent(TemplateMessage::class);

    Http::assertNothingSent();
});

it('inspects what was sent', function () {
    $fake = WhatsappToolkit::fake();

    WhatsappToolkit::to('333 123 4567')->html('<b>Ciao</b>')->send();

    $fake->assertSent(TextMessage::class, fn (CloudMessage $message): bool => $message->body === '*Ciao*');

    expect($fake->sent())->toHaveCount(1);
});

it('asserts that nothing was sent', function () {
    WhatsappToolkit::fake()->assertNothingSent();
});

it('answers with whatever the test queued', function () {
    $fake = WhatsappToolkit::fake();
    $fake->push(new Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse('wamid.QUEUED'));

    expect(WhatsappToolkit::to('333 123 4567')->text('Ciao')->send()->id)->toBe('wamid.QUEUED');
});

it('can be told to fail', function () {
    $fake = WhatsappToolkit::fake();
    $fake->push(new RateLimitException('slow down'));

    expect(fn () => WhatsappToolkit::to('333 123 4567')->text('Ciao')->send())
        ->toThrow(RateLimitException::class);
});
