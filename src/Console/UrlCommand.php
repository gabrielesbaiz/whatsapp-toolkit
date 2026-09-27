<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Console;

use Gabrielesbaiz\WhatsappToolkit\Contracts\Whatsapp;
use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;
use Illuminate\Console\Command;

final class UrlCommand extends Command
{
    protected $signature = 'whatsapp:url
                            {phone : The recipient, in any format}
                            {message? : An optional prefilled body, HTML allowed}
                            {--target= : wa.me, api, web, deep or business}';

    protected $description = 'Print a click-to-chat link for a phone number';

    public function handle(Whatsapp $whatsapp): int
    {
        $chat = $whatsapp->to($this->argument('phone'))->html($this->argument('message'));

        if (is_string($target = $this->option('target')) && ($resolved = LinkTarget::tryFrom($target)) !== null) {
            $chat = $chat->target($resolved);
        }

        $this->line((string) $chat->message());
        $this->newLine();
        $this->line($chat->url());

        return self::SUCCESS;
    }
}
