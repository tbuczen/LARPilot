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

## Investigating errors from Claude Code (Sentry MCP)

Once events are flowing, Claude Code can read them directly instead of being told
about them second-hand. `.mcp.json.dist` and `.claude/settings.json.dist` both
declare a `sentry` MCP server:

```json
"sentry": {
  "type": "stdio",
  "command": "npx",
  "args": ["-y", "@sentry/mcp-server@latest"],
  "env": {
    "SENTRY_ACCESS_TOKEN": "${SENTRY_ACCESS_TOKEN}",
    "SENTRY_HOST": "${SENTRY_HOST:-sentry.io}"
  }
}
```

It reads the token from the environment rather than storing it in the file,
because both templates are committed. Never paste the token in directly.

To enable it:

1. Copy the template if you have not already: `cp .mcp.json.dist .mcp.json`
   (`.mcp.json` is gitignored, so local edits stay local).
2. Create a **User Auth Token** in Sentry under *Settings → Account → User Auth
   Tokens*, with at least `event:read`, `project:read` and `org:read`.
3. Export it in the shell you start Claude Code from:

   ```bash
   export SENTRY_ACCESS_TOKEN=sntryu_...
   ```

4. Start a **new** Claude Code session — MCP servers are read at session start, so
   an already-running session will not pick it up.

Self-hosted Sentry: set `SENTRY_HOST` to your hostname (no scheme), e.g.
`SENTRY_HOST=sentry.example.com`.

### Remote alternative (OAuth)

Sentry also hosts the same server at `https://mcp.sentry.dev/mcp`, optionally
scoped as `/mcp/{organizationSlug}` or `/mcp/{organizationSlug}/{projectSlug}`.
It authenticates with OAuth rather than a token, which means an interactive
browser flow on first connect — fine on a workstation, awkward in a headless or
cloud session. The stdio server above is configured as the default for that
reason.

### Order of operations

The MCP server is only as useful as the data behind it. Until `SENTRY_DSN` is set
on the server (see *One-time setup* above) the project has no events, and the MCP
connection will authenticate successfully into an empty project. Set the DSN
first, confirm an event arrives, then wire up the MCP.

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
