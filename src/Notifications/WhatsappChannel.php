<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Notifications;

use Gabrielesbaiz\WhatsappToolkit\Cloud\Responses\MessageResponse;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudApi;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudMessage;
use Gabrielesbaiz\WhatsappToolkit\Contracts\PhoneNormalizer;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\InvalidPhoneNumberException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Notifications\Notification;

/**
 * Registers WhatsApp as a notification channel.
 *
 *     public function via($notifiable): array { return ['whatsapp']; }
 *
 *     public function toWhatsapp($notifiable): WhatsappMessage
 *     {
 *         return WhatsappMessage::template('order_shipped', 'it')->body($this->order->number);
 *     }
 *
 * The notifiable supplies the number through routeNotificationForWhatsapp(),
 * either as a string or as ['to' => …, 'from' => $phoneNumberId] when an
 * application sends from more than one business number.
 */
final class WhatsappChannel
{
    public function __construct(
        private readonly CloudApi $api,
        private readonly Repository $config,
        private readonly PhoneNormalizer $numbers,
    ) {}

    public function send(mixed $notifiable, Notification $notification): ?MessageResponse
    {
        // Dormant rather than broken: an application that has not configured
        // the Cloud API still runs, it simply does not send.
        if (! $this->config->get('whatsapp-toolkit.cloud.enabled')) {
            return null;
        }

        /** @var WhatsappMessage|CloudMessage $message */
        $message = $notification->toWhatsapp($notifiable); // @phpstan-ignore-line

        $route = $notifiable->routeNotificationFor('whatsapp', $notification);

        $api = $this->api;

        if (is_array($route)) {
            if (isset($route['from'])) {
                $api = $api->usingPhoneNumberId((string) $route['from']);
            }

            $route = $route['to'] ?? null;
        }

        $recipient = ($message instanceof WhatsappMessage ? $message->recipient() : $message->recipient())
            ?? (is_string($route) ? $route : null);

        if ($recipient === null || $recipient === '') {
            // Dropping a notification silently is the worse failure here.
            throw InvalidPhoneNumberException::missingRoute(get_debug_type($notifiable));
        }

        return $api->send(
            $message instanceof WhatsappMessage ? $message->toMessage() : $message,
            $this->numbers->normalize($recipient)->waId(),
        );
    }
}
