<?php

namespace App\Entity\Trait;

use App\Entity\User;

interface CreatorAwareInterface
{
    public function getCreatedBy(): ?User;

    public function setCreatedBy(User $user): self;
}