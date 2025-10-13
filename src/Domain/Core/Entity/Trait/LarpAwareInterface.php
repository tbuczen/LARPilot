<?php

namespace App\Domain\Core\Entity\Trait;

use App\Domain\Core\Entity\Larp;

interface LarpAwareInterface
{
    public function getLarp(): ?Larp;
}
