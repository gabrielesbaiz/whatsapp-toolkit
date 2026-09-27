<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Http\Controllers\WebhookController;
use Gabrielesbaiz\WhatsappToolkit\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

// The handshake carries no body, so there is nothing to sign; the POST route
// is the one that must prove where it came from.
Route::get('/', [WebhookController::class, 'verify'])->name('whatsapp-toolkit.webhook.verify');

Route::post('/', [WebhookController::class, 'handle'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('whatsapp-toolkit.webhook.handle');
