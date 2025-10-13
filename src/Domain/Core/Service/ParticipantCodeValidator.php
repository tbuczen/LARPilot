<?php

namespace App\Domain\Core\Service;

use App\Domain\Core\Entity\Larp;

final readonly class ParticipantCodeValidator
{
    public function validate(string $code, Larp $larp): bool
    {
        $parts = explode('-', $code);
        if (count($parts) !== 3) {
            return false;
        }
        [$prefix, $random, $checksum] = $parts;
        if ($prefix !== substr($larp->getId()->toRfc4122(), 0, 8)) {
            return false;
        }
        $expected = substr(hash('crc32b', $prefix . $random), 0, 4);
        return hash_equals($expected, $checksum);
    }
}
