<p align="center">
    <img src="art/whatsapp-toolkit-logo.png" alt="WhatsappToolkit" width="600">
</p>

# WhatsappToolkit

Click-to-chat links for Laravel — `wa.me/393331234567?text=Hi%20%2ASarah%2A` rather than a URL that opens WhatsApp and no conversation — with formatting, QR codes and Cloud API sending on top.

[![Latest version](https://img.shields.io/packagist/v/gabrielesbaiz/whatsapp-toolkit.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/whatsapp-toolkit)
[![PHP](https://img.shields.io/packagist/dependency-v/gabrielesbaiz/whatsapp-toolkit/php?style=flat-square)](composer.json)
[![Laravel](https://img.shields.io/packagist/dependency-v/gabrielesbaiz/whatsapp-toolkit/illuminate%2Fsupport?style=flat-square&label=laravel)](composer.json)
[![Downloads](https://img.shields.io/packagist/dt/gabrielesbaiz/whatsapp-toolkit.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/whatsapp-toolkit)
[![Stars](https://img.shields.io/github/stars/gabrielesbaiz/whatsapp-toolkit?style=flat-square&logo=github)](https://github.com/gabrielesbaiz/whatsapp-toolkit/stargazers)
[![Sponsor](https://img.shields.io/github/sponsors/gabrielesbaiz?style=flat-square&label=sponsor&logo=github)](https://github.com/sponsors/gabrielesbaiz)

### 📖 [Read the documentation →](https://gabrielesbaiz.github.io/whatsapp-toolkit/)

Every config key, the whole API, the Cloud API and its error codes, recipes for
the things that actually come up, and the errors you will hit with what each
one means.

> [!CAUTION]
> **Upgrading from 1.x?** Read [UPGRADE.md](UPGRADE.md) first. Phone numbers are
> now normalised rather than url-encoded, `format()` no longer returns encoded
> text, and the default link is `wa.me`. `url()` keeps its signature — but every
> link it produces is different, because the old ones opened a chat with nobody.

> [!IMPORTANT]
> A ⭐ costs you nothing and helps other developers find this package.
> [Sponsoring](https://github.com/sponsors/gabrielesbaiz) keeps it compatible
> with every new Laravel release.

## What it does

WhatsApp publishes the click-to-chat URL format, and it is two lines of string
concatenation. If your phone column already holds digits, your message is
already plain text, and nobody will ever paste rich text into it, write those
two lines and move on. This package is what those three assumptions cost when
they turn out to be false:

- **Phone normalisation** to the digits WhatsApp dials — punctuation, `00` prefixes and trunk zeros gone, a configurable country code applied, E.164 out.
- **HTML and Markdown to WhatsApp markup**, with entities decoded, ordered lists renumbered and link destinations kept — returned as plain text, so the same body also feeds a real send.
- **A Blade component, a validation rule, an Eloquent cast and Str macros**, so the correct thing is the short thing to write.
- **QR codes** for the chat link, and **five link flavours** including the native deep link and business short links.
- **Cloud API sending** — messages, templates, media, interactive buttons — with a notification channel, a fake for your tests, and a signature-verified inbound webhook.

The sending half stays completely dormant until you configure it: no routes, no
credential reads, nothing. Most applications install this for the links alone,
and it costs them nothing beyond Laravel to do so.

## Requirements

- PHP 8.3+
- Laravel 11, 12 or 13
- A Meta WhatsApp Business account — only if you want to send

## Installation

```bash
composer require gabrielesbaiz/whatsapp-toolkit

php artisan vendor:publish --tag=whatsapp-toolkit-config

php artisan whatsapp:url "333 123 4567" "<p>Hi <b>Sarah</b></p>"
```

The service provider is auto-discovered and there is nothing to register: no
migrations, no tables, no assets. Publishing is optional — the one setting worth
putting in `.env` straight away is `WHATSAPP_COUNTRY_CODE`, so local numbers
reach the right country.

**[Full installation guide →](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/install)**

## Artisan commands

| Command | Purpose |
|---|---|
| `whatsapp:url {phone} {message?}` | Print the formatted body and the link. `--target=` picks the flavour. |
| `whatsapp:status` | Show the resolved configuration, mask the token, and ping the Cloud API. |

See the
[commands page](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/commands).

## Documentation

| | |
|---|---|
| [Documentation site](https://gabrielesbaiz.github.io/whatsapp-toolkit/) | Everything: install, configure, operate. |
| [Guide](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/guide) | Links, numbers, formatting, QR, sending, webhook, Nova. |
| [Configuration](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/configuration) | Every key, its default, and what changing it does. |
| [API reference](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/api) | Every method, the message types, the fake, events and exceptions. |
| [Recipes](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/recipes) | Whole solutions to things that actually come up. |
| [Troubleshooting](https://gabrielesbaiz.github.io/whatsapp-toolkit/#/troubleshooting) | The errors people hit, and what each one means. |
| [UPGRADE.md](UPGRADE.md) | Upgrading from 1.x. Read before you start. |
| [CHANGELOG.md](CHANGELOG.md) | What changed, and when. |

## Testing

```bash
composer test        # Pest — 183 tests, 264 assertions
composer analyse     # PHPStan level 6
composer format      # Pint
composer bench       # the formatter, against the 1.x implementation
```

CI runs the first three on PHP 8.3 and 8.4 against Laravel 11 and 12, at both
lowest and stable dependencies.

## Contributing

Thank you for considering contributing. The guide is in
[CONTRIBUTING.md](CONTRIBUTING.md).

## Security vulnerabilities

Please review [SECURITY.md](SECURITY.md) for reporting a vulnerability. Please
do not open a public issue.

## Credits

Written and maintained by [Gabriele Sbaiz](https://github.com/gabrielesbaiz).

This package builds on Laravel,
[spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools),
and — when you ask for a QR code —
[bacon/bacon-qr-code](https://github.com/Bacon/BaconQrCode).

## Support this package

If it is useful to you:

- ⭐ **Star the repo.** Free, thirty seconds, and it is the first signal other developers look at.
- ❤️ **[Become a sponsor](https://github.com/sponsors/gabrielesbaiz).** From $5 a month.
- 🐛 **Open a good issue.** A phone format that normalises wrong, with the input, is worth more than you think.
- 🗣️ **Tell another Laravel developer.** Word of mouth is how packages survive.

[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-gabrielesbaiz-ff69b4?style=for-the-badge&logo=github-sponsors)](https://github.com/sponsors/gabrielesbaiz)

## Disclaimer

This package is provided **as is**, without warranty of any kind, express or
implied, including but not limited to the warranties of merchantability,
fitness for a particular purpose, title and non-infringement. To the fullest
extent permitted by applicable law, in no event shall the authors, copyright
holders or contributors be liable for any claim, damages or other liability —
whether in an action of contract, tort or otherwise — arising from, out of or in
connection with this package or its use, including without limitation any
direct, indirect, incidental, special, exemplary, consequential or punitive
damages, loss of data, loss of profits, business interruption, or messages
delivered to the wrong recipient.

It builds links and formats text; it cannot tell you whether a number belongs to
the person you think it does, or whether it has WhatsApp at all. A click-to-chat
link is public by construction — anything prefilled into one travels in a URL and
may appear in browser history and server logs, so do not prefill a secret. The
Cloud API half depends entirely on Meta's platform: its rate limits, template
approvals, 24-hour messaging window and error codes are theirs, not this
package's, and they change without notice. Whoever deploys this is responsible
for what is sent, to whom, and with what consent — including meeting whatever
regulatory, contractual or messaging-policy obligations apply to them, and
reviewing the code before putting it in front of a customer conversation.
Nothing here constitutes security, legal or compliance advice.

Use of this package is entirely at your own risk.

## License

MIT. See [LICENSE.md](LICENSE.md). The MIT licence's warranty disclaimer and
limitation of liability apply in full, alongside the disclaimer above.
