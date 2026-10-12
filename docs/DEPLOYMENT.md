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
