<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Doctrine\Type;

use App\Domain\Core\Doctrine\Type\EncryptedTextType;
use App\Domain\Core\Service\Encryption\SecretBoxCipher;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\TestCase;

class EncryptedTextTypeTest extends TestCase
{
    private EncryptedTextType $type;

    private PostgreSQLPlatform $platform;

    protected function setUp(): void
    {
        EncryptedTextType::useCipher(new SecretBoxCipher(sodium_crypto_secretbox_keygen()));
        $this->type = new EncryptedTextType();
        $this->platform = new PostgreSQLPlatform();
    }

    public function testValueIsStoredAsVersionedCiphertextAndReadBack(): void
    {
        $databaseValue = $this->type->convertToDatabaseValue('access-token', $this->platform);

        $this->assertIsString($databaseValue);
        $this->assertStringStartsWith('v1:', $databaseValue);
        $this->assertSame('access-token', $this->type->convertToPHPValue($databaseValue, $this->platform));
    }

    public function testLegacyPlaintextIsReadWithoutError(): void
    {
        $this->assertSame('legacy-token', $this->type->convertToPHPValue('legacy-token', $this->platform));
    }

    public function testNullStaysNull(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }
}
