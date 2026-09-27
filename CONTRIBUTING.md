# Contributing

Thanks for considering it. This is a small package and PRs are welcome.

## Setup

```bash
git clone git@github.com:gabrielesbaiz/whatsapp-toolkit.git
cd whatsapp-toolkit
composer install
```

## The three gates

CI runs all three; run them before you open a PR so it does not fail for you.

```bash
composer format    # pint, laravel preset plus strict types
composer analyse   # phpstan level 6 via larastan
composer test      # pest
```

`composer lint` runs the first two together.

## Ground rules

- **Every behaviour change needs a test that fails without it.** The suite runs
  in about two seconds; there is no excuse.
- **The link layer must stay dependency-free.** Building a URL may not need an
  HTTP client, a credential or a network. `tests/ArchTest.php` enforces this and
  it is not negotiable — a great many applications install this package for the
  links alone.
- **Formatting is not encoding.** `format()` returns plain WhatsApp markup;
  percent-escaping happens once, in `Chat::url()`. Fusing the two is what made
  version 1's output unusable anywhere but a query string.
- **Performance claims come with a measurement.** Touching `HtmlFormatter`
  means running `composer bench` before and after and putting the numbers in the
  PR.
- **Published samples are executed.** `tests/PublishedExamplesTest.php` runs
  every example on the documentation site and asserts the output it claims. If
  you change a sample on the site, change it there too — and if you change
  behaviour, that file is where the drift shows up first.
- **Comments explain why, not what.** If a line is surprising, say what it
  prevents.

## Working on the Cloud API layer

The whole sending half must stay dormant when it is not configured: no routes,
no credential reads, no exceptions on boot. Tests for it use two different
fakes, and the difference matters:

- `WhatsappToolkit::fake()` records message objects and never touches HTTP. Use
  it when the test is about behaviour — what was sent, to whom.
- `Http::fake()` intercepts real requests. Use it when the test is about the
  wire format, the retry policy, or error mapping. Something has to exercise the
  request building, and the recorder by definition does not.

Never commit a real token, phone number id or webhook payload from a live
account — the fixtures in `tests/` are deliberately fictional.

## Security

Please do not open a public issue for a vulnerability. See [SECURITY.md](SECURITY.md).
