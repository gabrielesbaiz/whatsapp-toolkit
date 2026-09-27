# Changelog

All notable changes to `whatsapp-toolkit` will be documented in this file.

## 2.0.0

A rewrite. See [UPGRADE.md](UPGRADE.md) for the migration, which is short.

### Fixed

- **Phone numbers are normalized rather than url-encoded.** `+39 333 123 4567`
  travelled as `%2B39+333+123+4567` and opened a chat with nobody; it is now
  `393331234567`.
- **HTML entities are decoded.** `&amp;`, `&egrave;` and `&#39;` no longer reach
  the chat window literally.
- **Ordered lists restart at 1.** A second `<ol>` in a message continued the
  first one's numbering.
- **Links keep their destination.** `<a href="…">qui</a>` lost its URL entirely.
- **Emphasis markers no longer carry stray padding spaces.**
- **Spaces in a prefilled body encode as `%20`**, not as a `+` that some clients
  render literally.

### Added

- A fluent, immutable `Chat` builder: `to() html() markdown() text() template()
  target() truncate()` and the terminals `url() link() qr() qrDataUri() send()`.
- Five link flavours — `wa.me`, `api.whatsapp.com`, `web.whatsapp.com`, the
  `whatsapp://` deep link and business short links.
- A `PhoneNumber` value object with `waId()`, `e164()`, `national()` and a
  configurable default country code.
- Markdown → WhatsApp and WhatsApp → HTML formatters.
- A `<x-whatsapp-link>` Blade component and a `@whatsappUrl` directive.
- A `WhatsappNumber` validation rule, an `AsWhatsappNumber` Eloquent cast, and
  `Str`/`Stringable` macros.
- QR codes for the chat link, via the suggested `bacon/bacon-qr-code`.
- Named message templates in config, with `:placeholder` substitution.
- A Meta Cloud API client: text, template, media, interactive, location and
  reaction messages, media upload and download, typed responses, and exceptions
  mapped from Meta's error codes with a hint naming the actual fix.
- A `whatsapp` notification channel and `WhatsappMessage` payload.
- `WhatsappToolkit::fake()` with `assertSent()`, `assertSentTo()`,
  `assertNothingSent()` and friends.
- An opt-in inbound webhook with `X-Hub-Signature-256` verification, idempotent
  delivery and events: `WhatsappMessageReceived`, `WhatsappStatusUpdated`,
  `WhatsappMessageFailed`, `WhatsappWebhookReceived`, `WhatsappMessageSent`.
- A Nova action for sending from a detail screen, when Nova is installed.
- `whatsapp:url` and `whatsapp:status` Artisan commands.
- A config file, contracts for every replaceable piece, and container bindings.
- CI: tests across PHP 8.3/8.4 and Laravel 11/12, PHPStan level 6, Pint.

### Changed

- Requires PHP 8.3 and Laravel 11, 12 or 13.
- Formatting and encoding are separate: `format()` returns plain WhatsApp
  markup, and percent-escaping happens once inside `url()`.
- `wa.me` is the default link flavour.
- The static API is gone; the package is an object bound in the container.

### Performance

Measured by `composer bench` against the 1.x implementation: plain text **31×**
faster, small HTML **1.9×**, rich editor output **1.4×**, and a repeated body
**230×** through the per-request memo.

## 1.3.0 and earlier

Initial releases: URL generator, HTML message formatter, phone number formatter.
