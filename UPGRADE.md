# Upgrading

## 1.x → 2.0

Version 2 is a rewrite. The three static methods are gone; the package is now an
ordinary object bound in the container, which is what makes it configurable,
swappable and fakeable. The changes at a call site are small and mechanical.

### Requirements

| | 1.x | 2.0 |
|---|---|---|
| PHP | 8.0 | 8.3 |
| Laravel | 10, 11, 12 | 11, 12, 13 |

### The API

```php
// 1.x
WhatsappToolkit::url($phone, $html);
WhatsappToolkit::formatMessage($html);      // returned url-encoded text
WhatsappToolkit::formatPhoneNumber($phone); // returned url-encoded text

// 2.0
WhatsappToolkit::url($phone, $html);        // unchanged, still the shortcut
WhatsappToolkit::to($phone)->html($html)->url();
WhatsappToolkit::format($html);             // returns plain WhatsApp markup
WhatsappToolkit::number($phone)->waId();    // returns a value object
```

`url()` survives with the same signature, so the most common call site needs no
change at all — but read on, because what it *produces* is different.

### What the output looks like now

**Phone numbers are normalized instead of url-encoded.** This is a bug fix, and
the most important reason to upgrade:

```php
// 1.x
WhatsappToolkit::url('+39 333 123 4567', 'Ciao');
// https://api.whatsapp.com/send?phone=%2B39+333+123+4567&text=Ciao   ← opens nothing

// 2.0
WhatsappToolkit::url('+39 333 123 4567', 'Ciao');
// https://wa.me/393331234567?text=Ciao
```

If your application prepends a country code by hand — `'+39' . $phone` — delete
that. Set `WHATSAPP_COUNTRY_CODE=39` instead and pass the stored number.

**The default link is now `wa.me`.** To keep the 1.x URL exactly, set:

```php
'link' => ['target' => LinkTarget::Api],
```

**Entities are decoded.** `&amp;`, `&egrave;` and `&#39;` used to reach the chat
window literally; they now arrive as `&`, `è` and `'`.

**Emphasis is no longer padded.** `<b>Bold</b> text` produced `` *Bold*  text``
with stray spaces; it now produces `*Bold* text`.

**Ordered lists restart.** A second `<ol>` in one message used to continue the
first one's numbering.

**Links keep their destination.** `<a href="…">qui</a>` used to become `qui`,
losing the URL; it now becomes `qui (https://…)`. Set `format.links` to `strip`
for the old behaviour.

**Spaces encode as `%20`, not `+`.** Some clients rendered the `+` literally.

### Formatting no longer encodes

`formatMessage()` returned URL-encoded text, which made it useless anywhere but
a query string. `format()` returns plain text, and encoding happens once inside
`url()`:

```php
// 1.x
$encoded = WhatsappToolkit::formatMessage($html);

// 2.0
$text = WhatsappToolkit::format($html);       // plain
$encoded = rawurlencode($text);               // only if you really need it
```

### formatPhoneNumber()

```php
// 1.x
WhatsappToolkit::formatPhoneNumber($phone);   // %2B39+333…

// 2.0
WhatsappToolkit::number($phone)->waId();      // 393331234567  (for WhatsApp)
WhatsappToolkit::number($phone)->e164();      // +393331234567 (for storage)
```

`number()` throws `InvalidPhoneNumberException` on something unusable. Use
`tryNumber()` for a null instead.

### Hand-written links in Blade

Replace them. The component gets the country code right and escapes the `&`
between query parameters, which hand-written markup usually does not:

```blade
{{-- Before --}}
<a href="https://api.whatsapp.com/send?phone={{ $phone }}&text={{ urlencode($subject) }}" target="_blank">
    Chatta su WhatsApp
</a>

{{-- After --}}
<x-whatsapp-link :to="$phone" :message="$subject">Chatta su WhatsApp</x-whatsapp-link>
```

### New configuration

Publish the config file and set your country code:

```bash
php artisan vendor:publish --tag="whatsapp-toolkit-config"
```

```dotenv
WHATSAPP_COUNTRY_CODE=39
```

Everything else has a working default, and the whole Cloud API layer stays
dormant until `WHATSAPP_CLOUD_ENABLED=true`.

### Removed

- `WhatsappToolkit::formatMessage()` → `format()`
- `WhatsappToolkit::formatPhoneNumber()` → `number()`
- The empty `database/factories` skeleton
