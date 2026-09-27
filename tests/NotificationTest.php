<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TemplateMessage;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Messages\TextMessage;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException;
use Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit;
use Gabrielesbaiz\WhatsappToolkit\Notifications\WhatsappMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class QuotationReady extends Notification
{
    public function __construct(private readonly bool $template = false) {}

    public function via(mixed $notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsapp(mixed $notifiable): WhatsappMessage
    {
        return $this->template
            ? WhatsappMessage::template('quotation_ready', 'it')->body('Q-7')
            : WhatsappMessage::text('Il preventivo è pronto.');
    }
}

class Dealer
{
    use Notifiable;

    public function __construct(private readonly ?string $phone = '333 123 4567') {}

    public function routeNotificationForWhatsapp(): ?string
    {
        return $this->phone;
    }
}

beforeEach(fn () => enableCloud());

it('sends a notification through the whatsapp channel', function () {
    $fake = WhatsappToolkit::fake();

    (new Dealer)->notify(new QuotationReady);

    $fake->assertSent(TextMessage::class, fn (CloudMessage $m, string $to): bool => $to === '393331234567');
});

it('sends an approved template', function () {
    $fake = WhatsappToolkit::fake();

    (new Dealer)->notify(new QuotationReady(template: true));

    $fake->assertSent(TemplateMessage::class, fn (CloudMessage $m): bool => $m->name === 'quotation_ready'
        && $m->toPayload()['template']['language']['code'] === 'it');
});

it('stays quiet while the cloud api is disabled', function () {
    config()->set('whatsapp-toolkit.cloud.enabled', false);

    $fake = WhatsappToolkit::fake();

    (new Dealer)->notify(new QuotationReady);

    $fake->assertNothingSent();
});

it('refuses to drop a notification that has nowhere to go', function () {
    WhatsappToolkit::fake();

    expect(fn () => (new Dealer(phone: null))->notify(new QuotationReady))
        ->toThrow(InvalidPhoneNumberException::class);
});
