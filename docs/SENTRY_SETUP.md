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
3. Add it to `.env.local` **on the server**, in the deploy directory, next to
   `DATABASE_URL` and the other production settings:

   ```dotenv
   SENTRY_DSN=https://<key>@o<org>.ingest.sentry.io/<project>
   ```

Deployment runs `make deploy` over SSH and does not write `.env.local`, so the
server's copy is the source of truth for every secret — nothing needs to be
added to GitHub. Sentry is active from the next `cache:clear`/`cache:warmup`,
which `make deploy` runs for you.

To try it locally first, put the DSN in your own `.env.local` and run with
`APP_ENV=prod`.

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

`SENTRY_RELEASE` tags every event with a version string, so an error can be
traced back to the code that shipped it. It is optional and currently unset.

Since `make deploy` runs `git pull` on the server, the checkout already knows
its own commit — the simplest way to populate it is a line in the server's
`.env.local`, refreshed by hand or by a `deploy` hook:

```dotenv
SENTRY_RELEASE=<commit sha>
```

Leaving it unset costs only the commit link on each event; everything else in
the report is unaffected.

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
