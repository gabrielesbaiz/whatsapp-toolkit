<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->each->not->toBeUsed();

arch('everything declares strict types')
    ->expect('Gabrielesbaiz\WhatsappToolkit')
    ->toUseStrictTypes();

arch('contracts are interfaces')
    ->expect('Gabrielesbaiz\WhatsappToolkit\Contracts')
    ->toBeInterfaces();

arch('enums are enums')
    ->expect('Gabrielesbaiz\WhatsappToolkit\Enums')
    ->toBeEnums();

arch('exceptions all descend from the package base')
    ->expect('Gabrielesbaiz\WhatsappToolkit\Exceptions')
    ->toExtend('Gabrielesbaiz\WhatsappToolkit\Exceptions\WhatsappToolkitException');

arch('the link layer knows nothing about sending')
    // Building a URL must never need an HTTP client, a credential or a network.
    ->expect(['Gabrielesbaiz\WhatsappToolkit\Formatters', 'Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber'])
    ->not->toUse([
        'Gabrielesbaiz\WhatsappToolkit\Cloud',
        'Illuminate\Support\Facades\Http',
    ]);

arch('the cloud client never reaches for the http facade')
    // It takes the factory by injection, which is what makes Http::fake() work.
    ->expect('Gabrielesbaiz\WhatsappToolkit\Cloud\CloudApiClient')
    ->not->toUse('Illuminate\Support\Facades\Http');
