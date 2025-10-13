<?php

namespace App\Domain\Core\Entity;

use App\Domain\StoryObject\Entity\Enum\TargetType;
use Symfony\Component\Uid\Uuid;

interface TargetableInterface
{
    public function getId(): Uuid;
    public static function getTargetType(): TargetType;
}
