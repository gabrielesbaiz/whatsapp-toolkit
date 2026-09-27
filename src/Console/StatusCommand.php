<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Console;

use Gabrielesbaiz\WhatsappToolkit\Exceptions\WhatsappToolkitException;
use Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

/**
 * Answers "why doesn't it send?" before anyone has to ask it.
 *
 * Nearly every Cloud API failure report turns out to be a missing credential or
 * the 24-hour quickstart token, so this command checks exactly those and says
 * so in one screen.
 */
final class StatusCommand extends Command
{
    protected $signature = 'whatsapp:status';

    protected $description = 'Show the resolved WhatsApp toolkit configuration and ping the Cloud API';

    public function handle(Repository $config, WhatsappToolkit $whatsapp): int
    {
        $cloud = (array) $config->get('whatsapp-toolkit.cloud');

        $this->components->twoColumnDetail('<fg=gray>Setting</>', '<fg=gray>Value</>');
        $this->components->twoColumnDetail('Default country code', (string) ($config->get('whatsapp-toolkit.default_country_code') ?? '<fg=yellow>none</>'));
        $this->components->twoColumnDetail('Link target', $whatsapp->to('+39000000000')->url());
        $this->components->twoColumnDetail('Cloud API', $cloud['enabled'] ? '<fg=green>enabled</>' : '<fg=yellow>disabled</>');

        if (! $cloud['enabled']) {
            $this->newLine();
            $this->components->info('Click-to-chat links work without any Cloud API credentials. Set WHATSAPP_CLOUD_ENABLED=true only if you want to send messages.');

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Phone number id', $this->present($cloud['phone_number_id'] ?? null));
        $this->components->twoColumnDetail('Business account id', $this->present($cloud['business_account_id'] ?? null));
        $this->components->twoColumnDetail('Access token', $this->mask((string) ($cloud['access_token'] ?? '')));
        $this->components->twoColumnDetail('API version', (string) ($cloud['api_version'] ?? ''));

        $this->newLine();

        try {
            $number = $whatsapp->cloud()->post(
                rtrim((string) $cloud['base_url'], '/').'/'.$cloud['api_version'].'/'.$cloud['phone_number_id'],
                [],
            );

            $this->components->info('Reached the Cloud API.');

            foreach (['display_phone_number', 'verified_name', 'quality_rating'] as $key) {
                if (isset($number[$key])) {
                    $this->components->twoColumnDetail(str_replace('_', ' ', $key), (string) $number[$key]);
                }
            }
        } catch (WhatsappToolkitException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function present(mixed $value): string
    {
        return is_string($value) && $value !== '' ? $value : '<fg=red>missing</>';
    }

    /**
     * Never print a token. A short one is almost certainly the 24-hour
     * quickstart token, which is worth warning about explicitly.
     */
    private function mask(string $token): string
    {
        if ($token === '') {
            return '<fg=red>missing</>';
        }

        $masked = substr($token, 0, 6).str_repeat('*', 12);

        return strlen($token) < 100
            ? $masked.' <fg=yellow>(looks like a temporary token; production needs a System User token)</>'
            : $masked;
    }
}
