# Deployment

Production deploys run on every push to `main` through `.github/workflows/deploy.yml`.
The workflow connects to the server over SSH, writes `.env.local` from the `prod`
environment's GitHub secrets and runs `make deploy` (git pull, Composer install,
migrations, integration token backfill, assets, cache, worker restart signal).

## Integration token encryption key

OAuth access and refresh tokens stored on `LarpIntegration` are encrypted at rest with
libsodium `crypto_secretbox` (Doctrine type `encrypted_text`,
`App\Domain\Core\Doctrine\Type\EncryptedTextType`). Stored values look like
`v1:<base64 nonce+ciphertext>`; the `v1` prefix is the key version used to encrypt them.
Values without a `v<n>:` prefix are treated as legacy plaintext and read as-is until the
backfill encrypts them.

| Variable | Meaning |
| --- | --- |
| `INTEGRATIONS_ENCRYPTION_KEY` | Current key, 32 random bytes, base64-encoded. Required. |
| `INTEGRATIONS_ENCRYPTION_KEY_VERSION` | Version number of the current key. Defaults to `1`. |
| `INTEGRATIONS_ENCRYPTION_RETIRED_KEYS` | Older keys that can still decrypt, as `<version>:<base64 key>` separated by commas. Empty by default. |

`.env` and `.env.test` ship an all-zero placeholder key (not a secret). It must never be used in
production: the deploy workflow refuses to run when the `INTEGRATIONS_ENCRYPTION_KEY`
secret is empty or still the all-zero placeholder.

### Generating the key

```bash
php -r 'echo base64_encode(sodium_crypto_secretbox_keygen()), PHP_EOL;'
```

Store the output as the `INTEGRATIONS_ENCRYPTION_KEY` secret on the `prod` environment
(GitHub → Settings → Environments → prod → Environment secrets), or with the GitHub CLI:

```bash
php -r 'echo base64_encode(sodium_crypto_secretbox_keygen());' | gh secret set INTEGRATIONS_ENCRYPTION_KEY --env prod --repo tbuczen/LARPilot
```

### Backing up the key

GitHub secrets cannot be read back once saved. Keep a copy of every key (current and
retired) in the team password manager, next to the database backups it belongs to.
A database backup is only useful together with the keys that encrypted it.

**Losing the key means every integration must be reconnected**: tokens encrypted with a
lost key cannot be recovered, and each organizer has to reconnect their Google, Discord,
Miro, etc. integration.

### Backfilling existing rows

```bash
php bin/console app:integrations:encrypt-tokens --dry-run
php bin/console app:integrations:encrypt-tokens
```

The command encrypts plaintext tokens and re-encrypts tokens stored with a retired key
version. Tokens already encrypted with the current key are skipped, so it is safe to run
repeatedly. `make deploy` runs it right after the migrations.

### Rotating the key

1. Generate a new key (see above) and back it up.
2. Update the `prod` environment secrets:
   - `INTEGRATIONS_ENCRYPTION_RETIRED_KEYS` → append the old key with its version, e.g.
     `1:<old base64 key>` (comma-separate when there are several);
   - `INTEGRATIONS_ENCRYPTION_KEY` → the new key;
   - `INTEGRATIONS_ENCRYPTION_KEY_VERSION` → the next version number, e.g. `2`.
3. Re-run the deploy workflow (`workflow_dispatch`). New writes use the new key, old values
   keep decrypting with the retired key, and the backfill re-encrypts every token to the
   new version.
4. Once `app:integrations:encrypt-tokens --dry-run` reports nothing to encrypt, the old
   key may be removed from `INTEGRATIONS_ENCRYPTION_RETIRED_KEYS` (keep it with any
   database backups taken before the rotation).

## Background worker (Messenger)

Integration syncs (Discord, Miro, …), queued e-mails and StoryAI indexing run as Symfony
Messenger messages. They are stored in the `messenger_messages` table and processed by a
long-running worker:

```bash
php bin/console messenger:consume async integrations scheduler_integrations --time-limit=3600 --memory-limit=256M --env=prod
```

`make worker-run` runs the same command in the foreground.

| Transport | What it carries |
| --- | --- |
| `async` | e-mails, notifier messages, StoryAI indexing |
| `integrations` | `SyncExternalSourceMessage` — fetches one page of a linked source, stores items and the cursor, re-queues itself while pages remain |
| `scheduler_integrations` | `IntegrationSyncSchedule` — every 5 minutes enqueues every enabled source whose `nextSyncAt` has passed |
| `failed` | messages that exhausted their retries (`messenger:failed:show`, `messenger:failed:retry`) |

The worker exits after an hour (`--time-limit`) or when `messenger:stop-workers` is
called, so it must run under a process manager that restarts it. `make deploy` calls
`messenger:stop-workers` at the end; this only writes a restart signal and succeeds
whether or not a worker is running, so a deploy never depends on the worker. Without a
worker nothing is lost: messages wait in `messenger_messages` until one starts.

One worker is enough. The schedule and each source sync hold a lock (`LOCK_DSN`, `flock`
by default), so a second worker on the same server is safe but never syncs one source twice
at the same time. Workers on different servers need a shared `LOCK_DSN` (for example
`postgresql+advisory://…`).

### supervisor

`/etc/supervisor/conf.d/larpilot-worker.conf`:

```ini
[program:larpilot-worker]
command=php -d memory_limit=-1 /path/to/LARPilot/bin/console messenger:consume async integrations scheduler_integrations --time-limit=3600 --memory-limit=256M --env=prod
directory=/path/to/LARPilot
user=www-data
numprocs=1
autostart=true
autorestart=true
startsecs=0
stopwaitsecs=30
stdout_logfile=/path/to/LARPilot/var/log/worker.log
redirect_stderr=true
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start larpilot-worker
```

### systemd

`/etc/systemd/system/larpilot-worker.service`:

```ini
[Unit]
Description=LARPilot Messenger worker
After=network.target postgresql.service

[Service]
User=www-data
WorkingDirectory=/path/to/LARPilot
ExecStart=/usr/bin/php -d memory_limit=-1 bin/console messenger:consume async integrations scheduler_integrations --time-limit=3600 --memory-limit=256M --env=prod
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload && sudo systemctl enable --now larpilot-worker
```

### cron (hosting without supervisor or systemd)

`flock -n` keeps a single worker alive; cron restarts it within a minute after it exits:

```cron
* * * * * cd /path/to/LARPilot && flock -n var/worker.lock php -d memory_limit=-1 bin/console messenger:consume async integrations scheduler_integrations --time-limit=3600 --memory-limit=256M --env=prod >> var/log/worker.log 2>&1
```

### Before starting the worker for the first time

Messages may already be waiting in `messenger_messages` (for example e-mails queued while
no worker ran). Check the backlog before the first start and delete anything that should
no longer be sent:

```sql
SELECT queue_name, count(*), min(created_at) FROM messenger_messages GROUP BY queue_name;
```

### Docker (local development)

`docker compose up -d` starts a `worker` service running the same command.

## Google Drive access scope

LARPilot asks Google only for `https://www.googleapis.com/auth/drive.file` (plus `email`).
With this scope the app can open only the files an organizer picks in the Google Picker
and the files it creates itself (exported character sheets, faction folders). The
service account keeps reading and writing the picked files through the Drive sharing
the picker applies, so imports and mapping previews do not depend on the organizer's
token.

The Picker must be built with the Cloud project number (`setAppId`), otherwise the
picked files are not granted to the app. Set `GOOGLE_APP_ID` to the project number; when
it is empty the number is taken from the prefix of `OAUTH_GOOGLE_CLIENT_ID`.

### Connections made before the switch

Integrations connected with the old full `drive` scope show a reconnect prompt in the
Google integration settings instead of the file picker. Each organizer has to:

1. click **Re-connect** and approve the new, narrower access;
2. open the file picker and select the files LARPilot uses again (spreadsheets,
   the character-sheet template and its folder), so they are granted to `drive.file`;
3. optionally remove the old full-Drive grant at
   <https://myaccount.google.com/permissions>.


## Discord bot

Organizers invite one shared LARPilot bot to their Discord server from
**Integrations → Discord → Connect**. The bot is read-only (permissions
`66560` = View Channel + Read Message History) and no Discord token is stored per
LARP: the callback exchanges the one-time code only to learn which server the bot
joined (Discord treats the `guild_id` query parameter as a hint), then revokes it.

### Developer portal setup

1. Open the application at <https://discord.com/developers/applications> (it can be
   the same application used for "Log in with Discord").
2. **Bot**: add a bot user, copy its token, turn on **Message Content Intent**
   (needed later to read message text), keep **Public Bot** on so organizers can
   invite it to their servers, and turn on **Requires OAuth2 Code Grant** so Discord
   always sends the bot invite back to LARPilot with a code (and the bot cannot be
   added outside LARPilot).
3. **OAuth2 → Redirects**: add
   `https://<your host>/backoffice/larp/integration/connect/discord/check`.
4. **Installation**: keep **Guild Install** enabled.

### Secrets

| Secret (`prod` environment) | Value |
| --- | --- |
| `DISCORD_BOT_TOKEN` | The bot token from the **Bot** page. |
| `DISCORD_CLIENT_ID` | The application id from **OAuth2**. Falls back to `OAUTH_DISCORD_CLIENT_ID` when empty. |
| `DISCORD_CLIENT_SECRET` | The client secret from **OAuth2**. Falls back to `OAUTH_DISCORD_CLIENT_SECRET` when empty. |

The deploy workflow writes all three into `.env.local`.

### Disconnecting

Disconnecting Discord from a LARP deletes that LARP's watched channels, everything
synced from them and the cached excerpts on its story links. The bot leaves the
server only when no other LARP is still connected to it.

## Miro integration

Organizers connect a Miro team per LARP through OAuth (`knpu_oauth2_client` client `integration_miro`).

1. Create an app at https://miro.com/app/settings/user-profile/apps and turn on
   expiring access tokens (the "Expire user authorization token" option): access tokens then last one hour and every refresh
   returns a new refresh token, which LARPilot stores under the token-refresh lock.
2. Add the redirect URI `https://<your-host>/backoffice/larp/integration/connect/integration_miro/check`
   (and `http://localhost:<port>/backoffice/larp/integration/connect/integration_miro/check` for local
   development).
3. Grant only the `boards:read` scope.
4. Store the app's client ID and secret as the `OAUTH_MIRO_CLIENT_ID` and `OAUTH_MIRO_CLIENT_SECRET`
   secrets on the `prod` environment. The deploy workflow writes them into `.env.local`; locally,
   set them in `.env.local` (`.env` only has empty placeholders).

## Deploy safety

- `make deploy` deletes `var/cache/prod` before `cache:clear`. `cache:clear` boots the kernel against the previously compiled container, so when new code asks for a service the old container lacks, it fails before it can rebuild anything and the site keeps returning HTTP 500.
- If the site is down after a deploy, run `rm -rf var/cache/prod && make deploy-no-pull` in the project directory on the server.
- `public/index.php` starts Sentry from `SENTRY_DSN` before the kernel boots (`BootErrorReporter`), so failures during kernel boot, such as a stale or broken container, reach Sentry. Errors after boot are still reported by the Sentry bundle.
- The deploy workflow requests `HEALTHCHECK_URL` (repository or `prod` environment variable, default `https://larpilot.com/`) after `make deploy` and fails the run when the site does not answer with a 2xx or 3xx status.
