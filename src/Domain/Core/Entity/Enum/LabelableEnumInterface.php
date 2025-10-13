<?php

namespace App\Domain\Core\Entity\Enum;

interface LabelableEnumInterface
{
    public function getLabel(): string;
}
