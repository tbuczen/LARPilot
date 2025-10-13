<?php

namespace App\Domain\Core\Entity\Trait;

use App\Domain\Account\Entity\User;

interface CreatorAwareInterface
{
    public function getCreatedBy(): ?User;

    public function setCreatedBy(User $user): self;
}
