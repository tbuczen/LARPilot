# Deployment

Production deploys run on every push to `main` through `.github/workflows/deploy.yml`.
The workflow connects to the server over SSH, writes `.env.local` from the `prod`
environment's GitHub secrets and runs `make deploy` (git pull, Composer install,
migrations, integration token backfill, assets, cache).

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

## Discord bot

Organizers invite one shared LARPilot bot to their Discord server from
**Integration settings → Discord → Connect**. The bot is read-only (permissions
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
