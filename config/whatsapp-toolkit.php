<?php

declare(strict_types=1);

use Gabrielesbaiz\WhatsappToolkit\Enums\LinkTarget;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | WhatsApp addresses people by an international number, but the numbers an
    | application stores are very often local ones typed by a human. This value
    | is prepended when a number arrives without a country code of its own, so
    | "333 123 4567" reaches the same person as "+39 333 123 4567".
    |
    | Set it to null to refuse local numbers outright, which is the safer choice
    | for an application with contacts in several countries.
    |
    */

    'default_country_code' => env('WHATSAPP_COUNTRY_CODE', '39'),

    /*
    |--------------------------------------------------------------------------
    | Click-to-chat Links
    |--------------------------------------------------------------------------
    |
    | "target" decides which flavour of link is produced. wa.me is the shortest
    | form and the one WhatsApp publishes, which also makes it the best choice
    | for QR codes; "api" is the longer api.whatsapp.com form this package
    | produced before version 2.
    |
    | A prefilled body travels inside a URL, and browsers, mail clients and
    | messaging apps all cut long URLs at different points. "max_length" is the
    | line this package will not cross, and "on_overflow" decides what happens
    | at it: "truncate" cuts at a word boundary, "throw" refuses loudly, and
    | "ignore" lets the link through whatever its length.
    |
    */

    'link' => [

        'target' => LinkTarget::WaMe,

        'max_length' => 4096,

        'on_overflow' => env('WHATSAPP_ON_OVERFLOW', 'truncate'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Message Formatting
    |--------------------------------------------------------------------------
    |
    | Rich-text editors produce HTML; WhatsApp understands its own small markup.
    | The formatter bridges the two, and memoizes as it goes, because rendering
    | one chat link per row of an index screen means formatting the same body
    | dozens of times per request. The memo lives and dies with the request.
    |
    | "links" decides what becomes of an anchor: "append" keeps the destination
    | by writing "label (https://…)", while "strip" keeps only the label, which
    | is what happens when HTML tags are simply removed.
    |
    */

    'format' => [

        'memo' => env('WHATSAPP_FORMAT_MEMO', true),

        'memo_size' => 128,

        'links' => 'append',

    ],

    /*
    |--------------------------------------------------------------------------
    | Named Message Templates
    |--------------------------------------------------------------------------
    |
    | Bodies that are used over and over belong here rather than in a controller
    | or a Nova action. Each one is Markdown, with :placeholders filled at call
    | time:
    |
    |     'quotation_followup' => 'Buongiorno :name, il preventivo :number è pronto.',
    |
    |     WhatsappToolkit::to($phone)
    |         ->template('quotation_followup', ['name' => $n, 'number' => $q])
    |         ->url();
    |
    | These are plain local templates, unrelated to the approved templates the
    | Cloud API requires for business-initiated conversations.
    |
    */

    'templates' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Codes
    |--------------------------------------------------------------------------
    |
    | Rendering needs bacon/bacon-qr-code, which is only suggested — nothing in
    | this package requires it until you ask for a QR. Error correction "M"
    | tolerates roughly 15% damage, which is the usual choice for a code that
    | will be printed on paper and photographed on a shop counter.
    |
    */

    'qr' => [

        'size' => 300,

        'margin' => 2,

        'error_correction' => 'M',

    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud API
    |--------------------------------------------------------------------------
    |
    | Everything below is dormant until "enabled" is true. Leaving it off costs
    | nothing: no credentials are read, no routes are registered, and the
    | notification channel returns without sending. That is what lets this
    | package stay a link builder for the people who only ever wanted one.
    |
    | Note that the access token offered by the Meta app dashboard's quickstart
    | panel expires after 24 hours. Production needs a System User token, which
    | does not. Run `php artisan whatsapp:status` to see which one you have.
    |
    */

    'cloud' => [

        'enabled' => env('WHATSAPP_CLOUD_ENABLED', false),

        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

        'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

        'timeout' => (int) env('WHATSAPP_TIMEOUT', 10),

        'connect_timeout' => (int) env('WHATSAPP_CONNECT_TIMEOUT', 5),

        /*
         * Only transient failures are retried: connection errors, 5xx, and the
         * rate-limit codes. A rejected template or an expired token is never
         * retried, because the second attempt fails in exactly the same way and
         * only spends quota doing it.
         */
        'retry' => [
            'times' => (int) env('WHATSAPP_RETRY_TIMES', 3),
            'sleep' => (int) env('WHATSAPP_RETRY_SLEEP', 200),
            'max' => (int) env('WHATSAPP_RETRY_MAX', 5000),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Inbound Webhook
    |--------------------------------------------------------------------------
    |
    | Disabled by default, and deliberately so: a publicly reachable POST route
    | appearing on an application because of a `composer update` would be a
    | security regression, not a feature.
    |
    | When you do enable it, "app_secret" is not optional. Every request is
    | checked against the X-Hub-Signature-256 header, and an unconfigured secret
    | is treated as a failure rather than as permission to skip the check.
    |
    | The package dispatches events and nothing more. It never replies, never
    | marks messages read, and never stores them — those are decisions for the
    | application that listens.
    |
    */

    'webhook' => [

        'enabled' => env('WHATSAPP_WEBHOOK_ENABLED', false),

        'path' => env('WHATSAPP_WEBHOOK_PATH', 'whatsapp/webhook'),

        'domain' => env('WHATSAPP_WEBHOOK_DOMAIN'),

        'middleware' => ['api'],

        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),

        'app_secret' => env('WHATSAPP_APP_SECRET'),

        /*
         * Meta redelivers anything it did not get a 2xx for, and occasionally
         * delivers twice even when it did. Message ids are remembered for a day
         * so a listener does not run twice for one message. An array cache
         * store cannot do this across processes; use a shared store in
         * production.
         */
        'idempotency' => [
            'enabled' => true,
            'store' => env('WHATSAPP_WEBHOOK_STORE'),
            'ttl' => 86400,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Off by default. Message bodies and phone numbers are personal data, and in
    | many deployments special-category data, so nothing is written unless a
    | channel is named here — and even then, bodies are redacted unless you
    | explicitly say otherwise.
    |
    */

    'logging' => [

        'channel' => env('WHATSAPP_LOG_CHANNEL'),

        'redact_content' => true,

    ],

];
