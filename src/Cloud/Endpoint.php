<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Cloud;

use Gabrielesbaiz\WhatsappToolkit\Exceptions\ConfigurationException;
use Illuminate\Contracts\Config\Repository;

/**
 * Where the Graph API lives, according to config.
 *
 * Kept apart from the client so that URL building can be asserted on its own,
 * and so that pointing the package at a sandbox is a config change rather than
 * a code change.
 */
final readonly class Endpoint
{
    public function __construct(private Repository $config) {}

    public function messages(?string $phoneNumberId = null): string
    {
        return $this->forNumber('messages', $phoneNumberId);
    }

    public function media(?string $phoneNumberId = null): string
    {
        return $this->forNumber('media', $phoneNumberId);
    }

    public function forNumber(string $path, ?string $phoneNumberId = null): string
    {
        $id = $phoneNumberId ?? $this->phoneNumberId();

        return $this->base()."/{$id}/{$path}";
    }

    /** An object addressed by its own id, such as a media object. */
    public function object(string $id): string
    {
        return $this->base()."/{$id}";
    }

    public function base(): string
    {
        $url = rtrim((string) $this->config->get('whatsapp-toolkit.cloud.base_url', 'https://graph.facebook.com'), '/');
        $version = trim((string) $this->config->get('whatsapp-toolkit.cloud.api_version', 'v21.0'), '/');

        return "{$url}/{$version}";
    }

    public function phoneNumberId(): string
    {
        $id = (string) $this->config->get('whatsapp-toolkit.cloud.phone_number_id');

        if ($id === '') {
            throw ConfigurationException::missing('cloud.phone_number_id');
        }

        return $id;
    }

    public function businessAccountId(): string
    {
        $id = (string) $this->config->get('whatsapp-toolkit.cloud.business_account_id');

        if ($id === '') {
            throw ConfigurationException::missing('cloud.business_account_id');
        }

        return $id;
    }
}
