#!/bin/bash
# Sets up this repo so Claude Code on the web can run Codeception, ECS,
# and PHPStan locally, matching what CI does, without Docker.
#
# Known sandbox quirks this works around (see comments inline):
#   - php8.4-bcmath / php8.4-apcu aren't preinstalled; `apt-get update` first
#     is required or the .deb fetch 404s.
#   - phpstan/phpstan ships no "source" (git) reference in Packagist
#     metadata, only a GitHub zipball "dist" URL. This sandbox's egress
#     proxy gates api.github.com behind the session's repo allowlist, so
#     that zipball 403s even though plain `git clone` of public repos
#     works unrestricted. We resolve it from a local shallow clone instead.
set -uo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

REPO_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$REPO_DIR" || exit 1

export COMPOSER_ALLOW_SUPERUSER=1

# --- PHP extensions needed for composer install / CI parity ------------
NEEDED_EXT=()
php -m | grep -qi '^bcmath$' || NEEDED_EXT+=("php8.4-bcmath")
php -m | grep -qi '^apcu$' || NEEDED_EXT+=("php8.4-apcu")
if [ "${#NEEDED_EXT[@]}" -gt 0 ]; then
  apt-get update -qq
  apt-get install -y -qq "${NEEDED_EXT[@]}"
fi

APCU_INI="/etc/php/8.4/cli/conf.d/20-apcu.ini"
if [ -f "$APCU_INI" ] && ! grep -q '^apc.enable_cli' "$APCU_INI"; then
  echo "apc.enable_cli=1" >> "$APCU_INI"
fi

# --- Composer: clear this sandbox's placeholder github-oauth entry -----
# Composer's own client-side validation rejects the literal string the
# sandbox pre-seeds ("proxy-injected") before ever sending a request, which
# makes every `composer config`/`composer install` invocation error out.
python3 - <<'PYEOF'
import json, pathlib
p = pathlib.Path.home() / ".config/composer/auth.json"
if p.exists():
    try:
        data = json.loads(p.read_text())
    except ValueError:
        data = {}
    if data.get("github-oauth", {}).get("github.com") == "proxy-injected":
        data["github-oauth"] = {}
        p.write_text(json.dumps(data, indent=4))
PYEOF

# --- Install PHP dependencies -------------------------------------------
COMPOSER_LOG=$(mktemp)
if ! composer install --no-interaction --prefer-source > "$COMPOSER_LOG" 2>&1; then
  if grep -q "phpstan/phpstan" "$COMPOSER_LOG" && grep -qi "authenticate against github.com" "$COMPOSER_LOG"; then
    echo "composer install hit the known phpstan/phpstan dist block; resolving from a local clone..."

    PHPSTAN_VERSION=$(php -r '
      $lock = json_decode(file_get_contents("composer.lock"), true);
      foreach ($lock["packages-dev"] as $pkg) {
        if ($pkg["name"] === "phpstan/phpstan") {
          echo $pkg["version"];
          break;
        }
      }
    ')
    PHPSTAN_TAG="${PHPSTAN_VERSION#v}"
    PHPSTAN_LOCAL=/root/.cache/local-repos/phpstan-phpstan

    if [ ! -d "$PHPSTAN_LOCAL/.git" ]; then
      mkdir -p "$PHPSTAN_LOCAL"
      git -C "$PHPSTAN_LOCAL" init -q
      git -C "$PHPSTAN_LOCAL" remote add origin https://github.com/phpstan/phpstan.git
    fi
    GIT_LFS_SKIP_SMUDGE=1 git -C "$PHPSTAN_LOCAL" fetch --depth 1 origin \
      "refs/tags/${PHPSTAN_TAG}:refs/tags/${PHPSTAN_TAG}"
    git -C "$PHPSTAN_LOCAL" checkout -q "tags/${PHPSTAN_TAG}"

    composer config repositories.phpstan-local \
      '{"type": "path", "url": "'"$PHPSTAN_LOCAL"'", "options": {"symlink": false}}'
    # This resolves and installs the ENTIRE dependency tree, not just
    # phpstan/phpstan; do not follow it with another `composer install` -
    # that would re-diff against the (about to be reverted) dist-only lock
    # entry and re-trigger the exact 403 this branch just worked around.
    composer update phpstan/phpstan --no-interaction --prefer-source
    # Local path repo is a machine-specific detour, not something to commit;
    # vendor/ (gitignored) keeps the already-installed files regardless.
    git checkout -- composer.json composer.lock
  else
    echo "composer install failed for a reason other than the known phpstan/phpstan block:"
    cat "$COMPOSER_LOG"
    exit 1
  fi
fi
rm -f "$COMPOSER_LOG"

# Regenerates vendor/autoload_runtime.php etc.; Composer plugins (Flex,
# Runtime) are disabled by default when running as root, hence the env var.
composer dump-autoload --no-interaction

# --- Postgres for functional tests --------------------------------------
service postgresql start >/dev/null 2>&1 || true
su postgres -c "psql -tAc \"SELECT 1 FROM pg_roles WHERE rolname='larpilot'\"" | grep -q 1 || \
  su postgres -c "psql -c \"CREATE ROLE larpilot LOGIN PASSWORD 'password' SUPERUSER\""

[ -f .env.test.local ] || echo "DATABASE_HOST=127.0.0.1" > .env.test.local

XDEBUG_MODE=off php -d memory_limit=-1 bin/console doctrine:database:create --if-not-exists --env=test
XDEBUG_MODE=off php -d memory_limit=-1 bin/console doctrine:migrations:migrate --no-interaction --env=test

# --- Frontend assets (functional tests render full pages via base.html.twig) --
XDEBUG_MODE=off php -d memory_limit=-1 bin/console importmap:install
XDEBUG_MODE=off php -d memory_limit=-1 bin/console sass:build || true

# --- Codeception actor classes ------------------------------------------
vendor/bin/codecept build
