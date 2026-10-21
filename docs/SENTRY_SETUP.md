# Sentry error monitoring

Production exceptions are reported to [Sentry](https://sentry.io) so a 500 on
larpilot.com comes with a stack trace, the request URL and the route parameters,
instead of having to be reproduced by hand.

Sentry is **only enabled in the `prod` environment** (`config/bundles.php`), and
even there it stays completely inert until `SENTRY_DSN` is set. Local
development and the test suite never send anything.

## One-time setup

1. Create a free account at <https://sentry.io> and add a new project of
   platform **PHP / Symfony**. The free tier covers 5k errors per month, which is
   far more than this app produces.
2. Copy the project's DSN — it looks like
   `https://<key>@o<org>.ingest.sentry.io/<project>`.
3. Add it to the repository as a secret named `SENTRY_DSN`, under the `prod`
   environment (Settings → Environments → prod → Add secret), next to
   `DATABASE_PASSWORD` and the others.

That is all. The next deploy writes the DSN into `.env.local` on the server and
exports it for the cache warmup, so Sentry is active from that release onward.

To try it locally first, put the DSN in `.env.local` and run with `APP_ENV=prod`.

## Checking it works

Every deploy ends by running `bin/console sentry:test` on the server with the
settings the website reads from `.env.local`, and prints the result in the
deploy log:

- `Message sent successfully with ID …` – a test event is in Sentry; errors are
  reported.
- `WARNING: SENTRY_DSN is empty` – the `SENTRY_DSN` secret is missing on the
  `prod` environment.
- `No DSN configured` – the DSN is set in GitHub but did not reach the
  website's `.env.local`.
- `Message not sent!` – the DSN is loaded but the server could not reach Sentry
  (outbound HTTPS to `*.sentry.io` blocked by the host's firewall, or a wrong
  DSN).

The same command can be run on the server by hand at any time.

## What gets reported

- Every unhandled exception in the `prod` environment.
- Every Monolog record at `error` level or above, from any channel, with the
  log's context attached.

Deliberately **not** reported:

- `NotFoundHttpException` / `MethodNotAllowedHttpException` — routine 404s and
  405s from crawlers would drown out real errors.
- `AccessDeniedException` / `AccessDeniedHttpException` — a 403 is the security
  layer working, not a bug.

Add to `ignore_exceptions` in `config/packages/sentry.yaml` if something else
turns out to be noise.

## Releases

The deploy workflow passes the deployed commit SHA as `SENTRY_RELEASE`, so every
event in Sentry is tagged with the exact commit it came from and you can jump
from an error straight to the code that shipped it.

## Privacy

`send_default_pii` is **off**, which is the conservative default: request
bodies, cookies, client IP addresses and the logged-in user's identity are all
withheld. You get the exception, the stack trace, the route and the URL — enough
to reproduce most bugs, but not who hit it.

If "which organizer hit this" turns out to matter more than withholding their
email, set `send_default_pii: true` in `config/packages/sentry.yaml`. Note this
is a real privacy trade-off, not just a verbosity knob: it starts sending user
identifiers and request data to a third party, so it belongs in the privacy
policy if you turn it on.

Performance tracing is off (`traces_sample_rate: 0.0`) — it is the part of
Sentry that consumes quota fastest, and error reporting is what this is for.
Raise it to something small like `0.05` if you ever want transaction timings.
