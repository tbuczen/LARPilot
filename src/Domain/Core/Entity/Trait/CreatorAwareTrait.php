<?php

namespace App\Domain\Core\Entity\Trait;

use App\Domain\Account\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait CreatorAwareTrait
{
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $user): self
    {
        $this->createdBy = $user;
        return $this;
    }
}
