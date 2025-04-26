<?php

namespace App\Entity\Trait;

use App\Entity\Larp;

interface LarpAwareInterface
{
    public function getLarp(): ?Larp;

}