<?php

namespace Gabrielesbaiz\WhatsappToolkit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit
 */
class WhatsappToolkit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Gabrielesbaiz\WhatsappToolkit\WhatsappToolkit::class;
    }
}
