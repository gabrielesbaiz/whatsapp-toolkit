# WhatsappToolkit

A lightweight helper package to handle WhatsApp messages using api.whatsapp.com

## Features

- ✅ URL generator for api.whatsapp.com
- ✅ Message formatter from HTML to WhatsApp
- ✅ PhoneNumber formatter

## Installation

You can install the package via composer:

```bash
composer require gabrielesbaiz/whatsapp-toolkit
```

Laravel will automatically register the service provider and facades.

If necessary, you can manually add the service provider in `config/app.php`:

```bash
'providers' => [
    Gabrielesbaiz\WhatsappToolkit\WhatsappToolkitServiceProvider::class,
],
```
And the facades:

```bash
'aliases' => [
    'WhatsappToolkit' => Gabrielesbaiz\WhatsappToolkit\Facades\WhatsappToolkit::class,
],
```

## Usage

```php
$whatsappToolkit = new Gabrielesbaiz\WhatsappToolkit();

echo $whatsappToolkit->url('+39 1234567890', '<p>Hallo world!</p>'); // https://api.whatsapp.com/send?phone=%2B39+1234567890&text=Hallo+world%21
```

Using facade:

```php
use WhatsappToolkit;

WhatsappToolkit::url('+39 1234567890', '<p>Hallo world!</p>'); // https://api.whatsapp.com/send?phone=%2B39+1234567890&text=Hallo+world%21
WhatsappToolkit::formatMessage('<p>Hallo world!</p>'); // Hallo+world%21
WhatsappToolkit::formatPhoneNumber('+39 1234567890'); // %2B39+1234567890
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Gabriele Sbaiz](https://github.com/gabrielesbaiz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
