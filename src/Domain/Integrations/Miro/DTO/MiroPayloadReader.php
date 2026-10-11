<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\DTO;

use DateTimeImmutable;
use Exception;

final class MiroPayloadReader
{
    /**
     * @param array<mixed> $payload
     */
    public static function string(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @param array<mixed> $payload
     */
    public static function float(array $payload, string $key): ?float
    {
        $value = $payload[$key] ?? null;
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @param array<mixed> $payload
     * @return array<mixed>
     */
    public static function map(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<mixed> $payload
     * @return list<array<mixed>>
     */
    public static function listOfMaps(array $payload, string $key): array
    {
        $maps = [];
        foreach (self::map($payload, $key) as $entry) {
            if (is_array($entry)) {
                $maps[] = $entry;
            }
        }

        return $maps;
    }

    /**
     * @param array<mixed> $payload
     * @return list<string>
     */
    public static function listOfStrings(array $payload, string $key): array
    {
        $strings = [];
        foreach (self::map($payload, $key) as $entry) {
            if (is_string($entry) || is_int($entry)) {
                $strings[] = (string) $entry;
            }
        }

        return $strings;
    }

    /**
     * @param array<mixed> $payload
     */
    public static function dateTime(array $payload, string $key): ?DateTimeImmutable
    {
        $value = self::string($payload, $key);
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param array<mixed> $payload
     */
    public static function nestedId(array $payload, string $key): ?string
    {
        $nested = $payload[$key] ?? null;
        if (is_array($nested)) {
            return self::string($nested, 'id');
        }

        return is_string($nested) || is_int($nested) ? (string) $nested : null;
    }
}
