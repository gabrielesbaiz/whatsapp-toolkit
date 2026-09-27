<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit;

use Gabrielesbaiz\WhatsappToolkit\Cloud\CloudApiClient;
use Gabrielesbaiz\WhatsappToolkit\Cloud\Endpoint;
use Gabrielesbaiz\WhatsappToolkit\Cloud\ErrorMapper;
use Gabrielesbaiz\WhatsappToolkit\Console\StatusCommand;
use Gabrielesbaiz\WhatsappToolkit\Console\UrlCommand;
use Gabrielesbaiz\WhatsappToolkit\Contracts\CloudApi;
use Gabrielesbaiz\WhatsappToolkit\Contracts\PhoneNormalizer;
use Gabrielesbaiz\WhatsappToolkit\Contracts\QrRenderer;
use Gabrielesbaiz\WhatsappToolkit\Contracts\Whatsapp;
use Gabrielesbaiz\WhatsappToolkit\Formatters\HtmlFormatter;
use Gabrielesbaiz\WhatsappToolkit\Formatters\MarkdownFormatter;
use Gabrielesbaiz\WhatsappToolkit\Formatters\WhatsappToHtmlFormatter;
use Gabrielesbaiz\WhatsappToolkit\Macros\StrMacros;
use Gabrielesbaiz\WhatsappToolkit\Notifications\WhatsappChannel;
use Gabrielesbaiz\WhatsappToolkit\Support\Memo;
use Gabrielesbaiz\WhatsappToolkit\Support\Normalizer;
use Gabrielesbaiz\WhatsappToolkit\Support\PhoneNumber;
use Gabrielesbaiz\WhatsappToolkit\Support\QrCode;
use Gabrielesbaiz\WhatsappToolkit\View\Components\WhatsappLink;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WhatsappToolkitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('whatsapp-toolkit')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommands([
                UrlCommand::class,
                StatusCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(HtmlFormatter::class, fn ($app): HtmlFormatter => new HtmlFormatter(
            $this->memo($app['config']),
            (string) $app['config']->get('whatsapp-toolkit.format.links', 'append'),
        ));

        $this->app->singleton(MarkdownFormatter::class, fn ($app): MarkdownFormatter => new MarkdownFormatter(
            $this->memo($app['config']),
        ));

        $this->app->singleton(WhatsappToHtmlFormatter::class);

        $this->app->singleton(PhoneNormalizer::class, fn ($app): Normalizer => new Normalizer(
            $this->countryCode($app['config']),
        ));

        $this->app->singleton(QrRenderer::class, fn ($app): QrCode => new QrCode(
            (int) $app['config']->get('whatsapp-toolkit.qr.size', 300),
            (int) $app['config']->get('whatsapp-toolkit.qr.margin', 2),
            (string) $app['config']->get('whatsapp-toolkit.qr.error_correction', 'M'),
        ));

        $this->app->singleton(Endpoint::class);
        $this->app->singleton(ErrorMapper::class);

        // Bound lazily, so an application that never sends anything never
        // resolves a client and never reads a credential.
        $this->app->bind(CloudApi::class, CloudApiClient::class);

        // Bound on the concrete class because that is what the facade accessor
        // resolves, and aliased to the contract for constructor injection.
        $this->app->singleton(WhatsappToolkit::class);
        $this->app->alias(WhatsappToolkit::class, Whatsapp::class);
        $this->app->alias(WhatsappToolkit::class, 'whatsapp-toolkit');
    }

    public function packageBooted(): void
    {
        // Every value object that normalizes a number on its own reads this,
        // which is why it is set once here rather than injected everywhere.
        PhoneNumber::$defaultCountryCode = $this->countryCode($this->app['config']);

        StrMacros::register($this->app->make(Whatsapp::class));

        Blade::component(WhatsappLink::class, 'whatsapp-link');

        Blade::directive('whatsappUrl', static fn (string $expression): string => "<?php echo e(app('whatsapp-toolkit')->url({$expression})); ?>");

        Notification::extend('whatsapp', fn ($app): WhatsappChannel => $app->make(WhatsappChannel::class));

        $this->registerWebhookRoutes();
    }

    /**
     * The inbound route exists only when it has been asked for.
     *
     * A publicly reachable POST endpoint appearing on an application because of
     * a `composer update` would be a security regression, not a feature.
     */
    private function registerWebhookRoutes(): void
    {
        $config = $this->app['config'];

        if (! $config->get('whatsapp-toolkit.webhook.enabled')) {
            return;
        }

        Route::group(array_filter([
            'prefix' => (string) $config->get('whatsapp-toolkit.webhook.path', 'whatsapp/webhook'),
            'middleware' => (array) $config->get('whatsapp-toolkit.webhook.middleware', ['api']),
            'domain' => $config->get('whatsapp-toolkit.webhook.domain'),
        ]), function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');
        });
    }

    private function memo(Repository $config): Memo
    {
        return new Memo(
            (int) $config->get('whatsapp-toolkit.format.memo_size', 128),
            (bool) $config->get('whatsapp-toolkit.format.memo', true),
        );
    }

    private function countryCode(Repository $config): ?string
    {
        $code = $config->get('whatsapp-toolkit.default_country_code');

        return is_string($code) && $code !== '' ? $code : null;
    }
}
