<?php

namespace App\Domain\Account\Entity;

use App\Domain\Account\Repository\PlanRepository;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
#[ORM\Table(name: 'plan')]
class Plan
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Maximum number of LARPs a user can create/organize.
     * NULL = unlimited
     */
    #[ORM\Column(nullable: true)]
    private ?int $maxLarps = null;

    /**
     * Maximum participants per LARP.
     * NULL = unlimited
     */
    #[ORM\Column(nullable: true)]
    private ?int $maxParticipantsPerLarp = null;

    /**
     * Storage limit in MB.
     * NULL = unlimited
     */
    #[ORM\Column(nullable: true)]
    private ?int $storageLimitMb = null;

    /**
     * Whether this plan includes Google integrations.
     */
    #[ORM\Column]
    private bool $hasGoogleIntegrations = false;

    /**
     * Whether this plan includes custom branding.
     */
    #[ORM\Column]
    private bool $hasCustomBranding = false;

    /**
     * Price in cents (USD). 0 = free.
     * This is a placeholder for future payment integration.
     */
    #[ORM\Column]
    private int $priceInCents = 0;

    /**
     * Whether this is a free plan.
     */
    #[ORM\Column]
    private bool $isFree = true;

    /**
     * Whether this plan is active and can be assigned to users.
     */
    #[ORM\Column]
    private bool $isActive = true;

    /**
     * Display order (lower numbers appear first).
     */
    #[ORM\Column]
    private int $sortOrder = 0;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getMaxLarps(): ?int
    {
        return $this->maxLarps;
    }

    public function setMaxLarps(?int $maxLarps): self
    {
        $this->maxLarps = $maxLarps;
        return $this;
    }

    public function getMaxParticipantsPerLarp(): ?int
    {
        return $this->maxParticipantsPerLarp;
    }

    public function setMaxParticipantsPerLarp(?int $maxParticipantsPerLarp): self
    {
        $this->maxParticipantsPerLarp = $maxParticipantsPerLarp;
        return $this;
    }

    public function getStorageLimitMb(): ?int
    {
        return $this->storageLimitMb;
    }

    public function setStorageLimitMb(?int $storageLimitMb): self
    {
        $this->storageLimitMb = $storageLimitMb;
        return $this;
    }

    public function hasGoogleIntegrations(): bool
    {
        return $this->hasGoogleIntegrations;
    }

    public function setHasGoogleIntegrations(bool $hasGoogleIntegrations): self
    {
        $this->hasGoogleIntegrations = $hasGoogleIntegrations;
        return $this;
    }

    public function hasCustomBranding(): bool
    {
        return $this->hasCustomBranding;
    }

    public function setHasCustomBranding(bool $hasCustomBranding): self
    {
        $this->hasCustomBranding = $hasCustomBranding;
        return $this;
    }

    public function getPriceInCents(): int
    {
        return $this->priceInCents;
    }

    public function setPriceInCents(int $priceInCents): self
    {
        $this->priceInCents = $priceInCents;
        return $this;
    }

    public function isFree(): bool
    {
        return $this->isFree;
    }

    public function setIsFree(bool $isFree): self
    {
        $this->isFree = $isFree;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isUnlimitedLarps(): bool
    {
        return $this->maxLarps === null;
    }

    public function isUnlimitedParticipants(): bool
    {
        return $this->maxParticipantsPerLarp === null;
    }

    public function isUnlimitedStorage(): bool
    {
        return $this->storageLimitMb === null;
    }
}
