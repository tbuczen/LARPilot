<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Enum\SubmissionStatus;
use App\Repository\LarpCharacterSubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: LarpCharacterSubmissionRepository::class)]
class LarpCharacterSubmission
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(length: 50)]
    private ?SubmissionStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getStatus(): ?SubmissionStatus
    {
        return $this->status;
    }

    public function setStatus(SubmissionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail ?? $this->getUser()->getContactEmail();
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

}
