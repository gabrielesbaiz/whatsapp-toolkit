<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Nova;

use Gabrielesbaiz\WhatsappToolkit\Contracts\Whatsapp;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Opens a prefilled WhatsApp conversation from a Nova detail screen.
 *
 * The recipient list comes from the resource itself: implement
 * whatsappRecipients() on the model and return value/label/group rows, exactly
 * the shape a Nova Select wants.
 *
 * This class is only registered when Nova is installed; the package does not
 * require it.
 */
class SendWhatsappMessage extends Action
{
    public $standalone = false;

    public $showInline = true;

    public $modalSize = '3xl';

    /** @var array<int, array{value: string, label: string, group?: string}> */
    protected array $recipients = [];

    protected string $recipientMethod = 'whatsappRecipients';

    public function name(): string
    {
        return __('Send a WhatsApp message');
    }

    /**
     * Name the model method that returns the recipient rows.
     */
    public function recipientsFrom(string $method): static
    {
        $this->recipientMethod = $method;

        return $this;
    }

    /**
     * @param  array<int, array{value: string, label: string, group?: string}>  $recipients
     */
    public function recipients(array $recipients): static
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * @param  Collection<int, object>  $models
     */
    public function handle(ActionFields $fields, Collection $models): ActionResponse
    {
        $whatsapp = app(Whatsapp::class);

        $url = $whatsapp->to((string) $fields->recipient)
            ->html((string) $fields->message)
            ->url();

        return ActionResponse::openInNewTab($url);
    }

    /**
     * @return array<int, object>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Select::make(__('Recipient'), 'recipient')
                ->options($this->resolveRecipients($request))
                ->displayUsingLabels()
                ->rules('required'),

            Textarea::make(__('Message'), 'message')
                ->rows(8)
                ->rules('required')
                ->help(__('Basic HTML is converted to WhatsApp formatting.')),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, group?: string}>
     */
    protected function resolveRecipients(NovaRequest $request): array
    {
        if ($this->recipients !== []) {
            return $this->recipients;
        }

        $model = rescue(fn () => $request->findModelQuery()?->first(), null, false);

        if ($model === null || ! method_exists($model, $this->recipientMethod)) {
            return [];
        }

        $recipients = $model->{$this->recipientMethod}();

        return $recipients instanceof Collection ? $recipients->all() : (array) $recipients;
    }
}
