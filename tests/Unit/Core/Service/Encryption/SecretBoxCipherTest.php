<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service\Encryption;

use App\Domain\Core\Service\Encryption\EncryptionException;
use App\Domain\Core\Service\Encryption\SecretBoxCipher;
use PHPUnit\Framework\TestCase;

class SecretBoxCipherTest extends TestCase
{
    public function testEncryptedValueRoundTrips(): void
    {
        $cipher = new SecretBoxCipher(sodium_crypto_secretbox_keygen());

        $encryptedToken = $cipher->encrypt('ya29.access-token');

        $this->assertStringStartsWith('v1:', $encryptedToken);
        $this->assertStringNotContainsString('ya29.access-token', $encryptedToken);
        $this->assertSame('ya29.access-token', $cipher->decrypt($encryptedToken));
    }

    public function testEncryptingTwiceUsesFreshNonces(): void
    {
        $cipher = new SecretBoxCipher(sodium_crypto_secretbox_keygen());

        $this->assertNotSame($cipher->encrypt('same-token'), $cipher->encrypt('same-token'));
    }

    public function testLegacyPlaintextIsReturnedUnchanged(): void
    {
        $cipher = new SecretBoxCipher(sodium_crypto_secretbox_keygen());

        $this->assertFalse($cipher->isEncrypted('1//legacy-refresh-token'));
        $this->assertSame('1//legacy-refresh-token', $cipher->decrypt('1//legacy-refresh-token'));
    }

    public function testValueEncryptedWithRetiredKeyStillDecryptsAfterRotation(): void
    {
        $firstKey = sodium_crypto_secretbox_keygen();
        $secondKey = sodium_crypto_secretbox_keygen();
        $tokenEncryptedWithFirstKey = (new SecretBoxCipher($firstKey))->encrypt('rotated-token');

        $rotatedCipher = new SecretBoxCipher($secondKey, 2, '1:' . base64_encode($firstKey));

        $this->assertSame('rotated-token', $rotatedCipher->decrypt($tokenEncryptedWithFirstKey));
        $this->assertFalse($rotatedCipher->isEncryptedWithCurrentKey($tokenEncryptedWithFirstKey));

        $reencryptedToken = $rotatedCipher->reencrypt($tokenEncryptedWithFirstKey);

        $this->assertStringStartsWith('v2:', $reencryptedToken);
        $this->assertTrue($rotatedCipher->isEncryptedWithCurrentKey($reencryptedToken));
        $this->assertSame('rotated-token', (new SecretBoxCipher($secondKey, 2))->decrypt($reencryptedToken));
    }

    public function testDecryptingWithUnknownKeyVersionFails(): void
    {
        $tokenEncryptedWithFirstKey = (new SecretBoxCipher(sodium_crypto_secretbox_keygen()))->encrypt('token');
        $cipherWithoutFirstKey = new SecretBoxCipher(sodium_crypto_secretbox_keygen(), 2);

        $this->expectException(EncryptionException::class);
        $cipherWithoutFirstKey->decrypt($tokenEncryptedWithFirstKey);
    }

    public function testDecryptingWithWrongKeyFails(): void
    {
        $encryptedToken = (new SecretBoxCipher(sodium_crypto_secretbox_keygen()))->encrypt('token');

        $this->expectException(EncryptionException::class);
        (new SecretBoxCipher(sodium_crypto_secretbox_keygen()))->decrypt($encryptedToken);
    }

    public function testKeyOfWrongLengthIsRejected(): void
    {
        $this->expectException(EncryptionException::class);
        (new SecretBoxCipher('too-short'))->encrypt('token');
    }

    public function testMalformedRetiredKeysAreRejected(): void
    {
        $this->expectException(EncryptionException::class);
        new SecretBoxCipher(sodium_crypto_secretbox_keygen(), 2, 'not-a-versioned-key');
    }
}
