<?php

declare(strict_types=1);

it('prints a link for a number', function () {
    $this->artisan('whatsapp:url', ['phone' => '333 123 4567', 'message' => '<b>Ciao</b>'])
        ->expectsOutputToContain('*Ciao*')
        ->expectsOutputToContain('https://wa.me/393331234567?text=%2ACiao%2A')
        ->assertSuccessful();
});

it('honours a target option', function () {
    $this->artisan('whatsapp:url', ['phone' => '333 123 4567', '--target' => 'api'])
        ->expectsOutputToContain('https://api.whatsapp.com/send?phone=393331234567')
        ->assertSuccessful();
});

it('reports that links work without any credentials', function () {
    $this->artisan('whatsapp:status')
        ->expectsOutputToContain('Click-to-chat links work without any Cloud API credentials')
        ->assertSuccessful();
});

it('never prints the access token', function () {
    enableCloud(['whatsapp-toolkit.cloud.access_token' => 'SECRET-TOKEN-VALUE']);
    fakeGraph(['display_phone_number' => '+39 000', 'verified_name' => 'Novias', 'quality_rating' => 'GREEN']);

    $this->artisan('whatsapp:status')
        ->doesntExpectOutputToContain('SECRET-TOKEN-VALUE')
        ->expectsOutputToContain('looks like a temporary token')
        ->assertSuccessful();
});
