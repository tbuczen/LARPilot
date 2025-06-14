<?php

namespace App\Entity;

use App\Entity\Enum\TargetType;
use Symfony\Component\Uid\Uuid;

interface TargetableInterface
{
    public function getId(): Uuid;
    public static function getTargetType(): TargetType;
}
