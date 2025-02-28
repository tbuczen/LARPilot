<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpInvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: LarpInvitationRepository::class)]
class LarpInvitation
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $code = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $validTo = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->code = bin2hex(random_bytes(16));
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeImmutable $validTo): self
    {
        $this->validTo = $validTo;
        return $this;
    }

}
