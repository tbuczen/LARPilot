<?php

declare(strict_types=1);

namespace App\Domain\Core\Doctrine\Type;

use App\Domain\Core\Service\Encryption\EncryptionException;
use App\Domain\Core\Service\Encryption\SecretBoxCipher;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

class EncryptedTextType extends TextType
{
    public const NAME = 'encrypted_text';

    private static ?SecretBoxCipher $cipher = null;

    public static function useCipher(SecretBoxCipher $cipher): void
    {
        self::$cipher = $cipher;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::cipher()->encrypt((string) $value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return self::cipher()->decrypt((string) $value);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    private static function cipher(): SecretBoxCipher
    {
        if (self::$cipher === null) {
            throw new EncryptionException('EncryptedTextType has no cipher configured.');
        }

        return self::$cipher;
    }
}
