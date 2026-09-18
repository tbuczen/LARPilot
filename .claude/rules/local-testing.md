# Running tests and quality tools locally (Claude Code on the web)

A `SessionStart` hook (`.claude/hooks/session-start.sh`, registered in
`.claude/settings.json`) sets up this repo on Claude Code's web sandbox so
Codeception, ECS, and PHPStan can run **without Docker**. On a fresh remote
session it installs missing PHP extensions, starts a local Postgres
instance with the `larpilot_test` database migrated, builds frontend
assets, and generates Codeception actors.

Prefer running the real commands locally over guessing from CI logs:

```bash
vendor/bin/ecs check <path>
XDEBUG_MODE=off vendor/bin/phpstan analyse -c phpstan.neon <path>
XDEBUG_MODE=off vendor/bin/codecept run Unit <path>
XDEBUG_MODE=off vendor/bin/codecept run Functional <path> --env test
```

If `vendor/bin/codecept run Functional` fails with `APCu is not enabled` or
`Symfony Runtime is missing`, the hook hasn't completed — re-run
`.claude/hooks/session-start.sh` rather than patching around it.

## Known environment gotchas (do not "fix" these in application code)

- **`phpstan/phpstan` has no git `source`** in its Packagist metadata — it's
  dist-only (a GitHub zipball URL). The sandbox's egress proxy gates
  `api.github.com` behind this session's repo allowlist, so that zipball
  403s even though plain `git clone` of public GitHub repos works
  unrestricted. The hook resolves it from a local shallow clone via a
  temporary `path` repository, then reverts `composer.json`/`composer.lock`
  — `vendor/` (gitignored) keeps the installed files. Never commit a
  `repositories.phpstan-local` entry; if you see one in a diff, drop it.
- **Composer plugins (Symfony Flex, Runtime) are disabled by default when
  running as root.** Without `COMPOSER_ALLOW_SUPERUSER=1`, `composer
  install`/`dump-autoload` silently skips generating
  `vendor/autoload_runtime.php`, and `bin/console` fails with `Symfony
  Runtime is missing`. Always export it before running Composer here.
- **APCu isn't installed by default**, and `config/packages/framework.yaml`
  configures `cache.app: cache.adapter.apcu` with no test-env override —
  functional tests crash with `APCu is not enabled` until `php8.4-apcu` is
  installed and `apc.enable_cli=1` is set for the CLI SAPI.
- **`bin/console sass:build` must run before functional tests** that render
  any page extending `base.html.twig` — without a built
  `var/sass/app.output.css`, every such request 500s with `The file
  .../app.output.css doesn't exist`. This isn't a real app bug; it's a
  missing build step.

## Codeception form-submission gotcha

`templates/base.html.twig` includes `components/FeedbackModal.html.twig`
and `components/CookieConsent.html.twig` **before** `{% block body %}`, and
both contain their own `<form>` element. A generic
`$I->submitForm('form', [...])` selector matches the *first* `<form>` on
the page — one of those — not your page's actual form, and silently
no-ops (test gets a 200 back instead of the expected redirect, with no
error). Always scope the selector to the real form, e.g.
`$I->submitForm('form[name="knowledge_document"]', [...])` — Symfony's
`form_start()` renders `name="{{ form.vars.name }}"`, which defaults to the
form type's block-prefix-derived name.
