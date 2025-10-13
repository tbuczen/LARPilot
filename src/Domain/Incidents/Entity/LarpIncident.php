<?php

namespace App\Domain\Incidents\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\LarpAwareInterface;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Incidents\Entity\Enum\LarpIncidentStatus;
use App\Domain\Incidents\Repository\LarpIncidentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LarpIncidentRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Index(columns: ['created_by_id'])]
class LarpIncident implements LarpAwareInterface, CreatorAwareInterface
{
    use UuidTraitEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\Column(length: 64)]
    private ?string $reportCode = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $caseId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $allowFeedback = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $contactAccused = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $allowMediator = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $stayAnonymous = false;

    #[ORM\Column(enumType: LarpIncidentStatus::class)]
    private LarpIncidentStatus $status = LarpIncidentStatus::NEW;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $needsPoliceSupport = null;

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getReportCode(): ?string
    {
        return $this->reportCode;
    }

    public function setReportCode(string $reportCode): self
    {
        $this->reportCode = $reportCode;
        return $this;
    }

    public function getCaseId(): ?string
    {
        return $this->caseId;
    }

    public function setCaseId(string $caseId): self
    {
        $this->caseId = $caseId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isAllowFeedback(): bool
    {
        return $this->allowFeedback;
    }

    public function setAllowFeedback(bool $allowFeedback): self
    {
        $this->allowFeedback = $allowFeedback;
        return $this;
    }

    public function isContactAccused(): bool
    {
        return $this->contactAccused;
    }

    public function setContactAccused(bool $contactAccused): self
    {
        $this->contactAccused = $contactAccused;
        return $this;
    }

    public function isAllowMediator(): bool
    {
        return $this->allowMediator;
    }

    public function setAllowMediator(bool $allowMediator): self
    {
        $this->allowMediator = $allowMediator;
        return $this;
    }

    public function isStayAnonymous(): bool
    {
        return $this->stayAnonymous;
    }

    public function setStayAnonymous(bool $stayAnonymous): self
    {
        $this->stayAnonymous = $stayAnonymous;
        return $this;
    }

    public function getStatus(): LarpIncidentStatus
    {
        return $this->status;
    }

    public function setStatus(LarpIncidentStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getNeedsPoliceSupport(): ?bool
    {
        return $this->needsPoliceSupport;
    }

    public function setNeedsPoliceSupport(?bool $needsPoliceSupport): self
    {
        $this->needsPoliceSupport = $needsPoliceSupport;
        return $this;
    }
}
