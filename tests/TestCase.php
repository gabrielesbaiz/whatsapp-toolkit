<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Tests;

use Gabrielesbaiz\WhatsappToolkit\WhatsappToolkitServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappToolkitServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('whatsapp-toolkit.default_country_code', '39');
        $app['config']->set('whatsapp-toolkit.cloud.enabled', false);
    }
}
