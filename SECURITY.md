# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 2.x | Yes |
| 1.x | No — upgrade, see [UPGRADE.md](UPGRADE.md) |

## Reporting a vulnerability

Email **gabriele@sbaiz.com** with a description, the package version, and a
reproduction if you have one. Please do not open a public issue.

You will get an acknowledgement within 5 working days and an assessment within
15. If the report is valid you will be credited in the release notes unless you
ask not to be.

## Scope

**In scope:** a webhook request being accepted without a valid signature, an
access token or phone number appearing in a log line or an exception message, an
inbound message body producing markup that escapes into a rendered page, a
crafted phone number or message body causing this package to address a
conversation other than the one asked for, and any crash reachable from a value
an application would reasonably pass in.

**Out of scope:** how an application stores the numbers and message bodies it
passes here, and anything decided by Meta's platform rather than by this code.

## What the package defends against

**Webhook forgery.** Every inbound POST is checked against the
`X-Hub-Signature-256` header, with the HMAC computed over the raw request body
and compared using `hash_equals()`. A missing or empty `app_secret` is treated
as a failure, never as permission to skip the check, and the rejection is a bare
403 that says nothing about why.

**Accidental exposure of the webhook.** The route is not registered unless
`webhook.enabled` is explicitly true. Installing or updating the package cannot
add a publicly reachable endpoint to an application.

**Leaking message content.** Nothing is logged unless a channel is named in
`logging.channel`, and even then message bodies are redacted by default.
Inbound message text is personal data, and in many deployments special-category
data.

**Leaking credentials.** `whatsapp:status` masks the access token, and
exceptions are built from the parsed error body rather than from the HTTP
client's request context, which would otherwise carry the `Authorization`
header.

**Injection into a rendered page.** `WhatsappToHtmlFormatter` escapes its input
before adding any markup, so an inbound message cannot inject tags into the page
that displays it. The Blade component escapes both the URL and the label.

## What it does not defend against

It cannot tell you whether a number belongs to the person you think it does, nor
whether it has WhatsApp at all. It validates shape, not identity.

Click-to-chat links are public by construction: anything you prefill into one
travels in a URL, through the user's browser, and may appear in history and
logs. Do not prefill a secret.
