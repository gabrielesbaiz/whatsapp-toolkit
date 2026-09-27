<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Casts\AsWhatsappNumber;
use Gabrielesbaiz\WhatsappToolkit\Rules\WhatsappNumber;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

it('passes validation for a reachable number', function (string $value) {
    expect(Validator::make(['phone' => $value], ['phone' => [new WhatsappNumber]])->passes())->toBeTrue();
})->with(['+39 333 123 4567', '333 123 4567', '0039 333 1234567']);

it('fails validation for anything else', function (mixed $value) {
    expect(Validator::make(['phone' => $value], ['phone' => [new WhatsappNumber]])->passes())->toBeFalse();
})->with(['nonsense', '12', [[]]]);

it('requires international form when asked to', function () {
    $rule = ['phone' => [WhatsappNumber::international()]];

    expect(Validator::make(['phone' => '333 123 4567'], $rule)->passes())->toBeFalse()
        ->and(Validator::make(['phone' => '+39 333 123 4567'], $rule)->passes())->toBeTrue();
});

it('normalises a number on the way into the database', function () {
    $model = new class extends Model
    {
        protected $guarded = [];

        protected $casts = ['mobile_phone' => AsWhatsappNumber::class];
    };

    $model->mobile_phone = '333 123 4567';

    expect($model->getAttributes()['mobile_phone'])->toBe('+393331234567')
        ->and($model->mobile_phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($model->mobile_phone->waId())->toBe('393331234567');
});

it('stores null rather than mangling an unusable number', function () {
    $model = new class extends Model
    {
        protected $guarded = [];

        protected $casts = ['mobile_phone' => AsWhatsappNumber::class];
    };

    $model->mobile_phone = 'nonsense';

    expect($model->getAttributes()['mobile_phone'])->toBeNull();
});

it('adds string helpers', function () {
    expect(Str::whatsappNumber('333 123 4567'))->toBe('+393331234567')
        ->and((string) str('333 123 4567')->toWhatsappUrl('Ciao'))
        ->toBe('https://wa.me/393331234567?text=Ciao');
});
