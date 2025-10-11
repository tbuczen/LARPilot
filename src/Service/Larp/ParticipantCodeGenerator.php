<?php

namespace App\Service\Larp;

use App\Entity\Larp;

final readonly class ParticipantCodeGenerator
{
    public function generate(Larp $larp): string
    {
        $prefix = substr($larp->getId()->toRfc4122(), 0, 8);
        $random = bin2hex(random_bytes(3));
        $checksum = substr(hash('crc32b', $prefix . $random), 0, 4);
        return sprintf('%s-%s-%s', $prefix, $random, $checksum);
    }
}
