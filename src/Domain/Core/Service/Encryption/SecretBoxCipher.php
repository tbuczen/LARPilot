<?php

declare(strict_types=1);

namespace App\Domain\Core\Service\Encryption;

class SecretBoxCipher
{
    private const VERSION_PATTERN = '/^v(\d+):/';

    /** @var array<int, string> */
    private readonly array $keysByVersion;

    public function __construct(
        string $currentKey,
        private readonly int $currentVersion = 1,
        string $retiredKeys = '',
    ) {
        $keysByVersion = self::parseRetiredKeys($retiredKeys);
        $keysByVersion[$currentVersion] = $currentKey;
        $this->keysByVersion = $keysByVersion;
    }

    public static function generateKey(): string
    {
        return base64_encode(sodium_crypto_secretbox_keygen());
    }

    public static function versionOf(string $value): ?int
    {
        if (preg_match(self::VERSION_PATTERN, $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    public function isEncrypted(string $value): bool
    {
        return self::versionOf($value) !== null;
    }

    public function isEncryptedWithCurrentKey(string $value): bool
    {
        return self::versionOf($value) === $this->currentVersion;
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->keyFor($this->currentVersion);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return sprintf('v%d:%s', $this->currentVersion, base64_encode($nonce . $ciphertext));
    }

    public function decrypt(string $value): string
    {
        $version = self::versionOf($value);
        if ($version === null) {
            return $value;
        }

        $payload = base64_decode(substr($value, strpos($value, ':') + 1), true);
        if ($payload === false || strlen($payload) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            throw new EncryptionException(sprintf('Encrypted value with key version %d is malformed.', $version));
        }

        $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->keyFor($version));
        if ($plaintext === false) {
            throw new EncryptionException(sprintf('Encrypted value could not be decrypted with key version %d.', $version));
        }

        return $plaintext;
    }

    public function reencrypt(string $value): string
    {
        return $this->encrypt($this->decrypt($value));
    }

    private function keyFor(int $version): string
    {
        $key = $this->keysByVersion[$version] ?? null;
        if ($key === null) {
            throw new EncryptionException(sprintf('No encryption key is configured for key version %d.', $version));
        }

        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new EncryptionException(sprintf(
                'Encryption key version %d must be %d bytes (base64-encoded), got %d.',
                $version,
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
                strlen($key),
            ));
        }

        return $key;
    }

    /**
     * @return array<int, string>
     */
    private static function parseRetiredKeys(string $retiredKeys): array
    {
        $keysByVersion = [];
        foreach (array_filter(array_map('trim', explode(',', $retiredKeys))) as $retiredKey) {
            $parts = explode(':', $retiredKey, 2);
            if (count($parts) !== 2 || !ctype_digit($parts[0])) {
                throw new EncryptionException('Retired encryption keys must be formatted as "<version>:<base64 key>", separated by commas.');
            }

            $key = base64_decode($parts[1], true);
            if ($key === false) {
                throw new EncryptionException(sprintf('Retired encryption key version %s is not valid base64.', $parts[0]));
            }

            $keysByVersion[(int) $parts[0]] = $key;
        }

        return $keysByVersion;
    }
}
