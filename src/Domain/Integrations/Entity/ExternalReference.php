<?php

namespace App\Domain\Integrations\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Service\ExcerptNormalizer;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[Gedmo\Loggable]
#[ORM\Entity(repositoryClass: ExternalReferenceRepository::class)]
#[ORM\Index(columns: ['created_by_id'])]
#[ORM\UniqueConstraint(name: 'uniq_external_reference_story_object_provider_external_id', columns: ['story_object_id', 'provider', 'external_id'])]
class ExternalReference implements CreatorAwareInterface, Timestampable
{
    use UuidTraitEntity;
    use CreatorAwareTrait;
    use TimestampableEntity;

    #[ORM\Column(type: 'string', enumType: LarpIntegrationProvider::class)]
    private LarpIntegrationProvider $provider;

    #[ORM\Column(type: 'string')]
    private string $externalId;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: 'string', enumType: ReferenceType::class)]
    private ReferenceType $referenceType;

    #[ORM\Column(type: 'string', enumType: ReferenceRole::class)]
    private ReferenceRole $role;

    #[ORM\ManyToOne(targetEntity: StoryObject::class, inversedBy: 'externalReferences')]
    #[ORM\JoinColumn(nullable: true)]
    private ?StoryObject $storyObject = null;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Larp $larp;

    #[ORM\ManyToOne(targetEntity: ExternalSource::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ExternalSource $source = null;

    #[ORM\ManyToOne(targetEntity: ExternalItem::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ExternalItem $externalItem = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $externalUpdatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    private ?TargetType $storyObjectType = null;

    public function getProvider(): LarpIntegrationProvider
    {
        return $this->provider;
    }

    public function setProvider(LarpIntegrationProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getReferenceType(): ReferenceType
    {
        return $this->referenceType;
    }

    public function setReferenceType(ReferenceType $referenceType): void
    {
        $this->referenceType = $referenceType;
    }

    public function getRole(): ReferenceRole
    {
        return $this->role;
    }

    public function setRole(ReferenceRole $role): void
    {
        $this->role = $role;
    }

    public function getStoryObject(): ?StoryObject
    {
        return $this->storyObject;
    }

    public function setStoryObject(?StoryObject $storyObject): self
    {
        $this->storyObject = $storyObject;
        $storyObjectLarp = $storyObject?->getLarp();
        if ($storyObjectLarp !== null) {
            $this->larp = $storyObjectLarp;
        }
        return $this;
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getSource(): ?ExternalSource
    {
        return $this->source;
    }

    public function setSource(?ExternalSource $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getExternalItem(): ?ExternalItem
    {
        return $this->externalItem;
    }

    public function setExternalItem(?ExternalItem $externalItem): self
    {
        $this->externalItem = $externalItem;
        return $this;
    }

    public function getExternalUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->externalUpdatedAt;
    }

    public function setExternalUpdatedAt(?\DateTimeImmutable $externalUpdatedAt): self
    {
        $this->externalUpdatedAt = $externalUpdatedAt;
        return $this;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): self
    {
        $this->lastSeenAt = $lastSeenAt;
        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = ExcerptNormalizer::normalize($excerpt);
        return $this;
    }

    public function getStoryObjectType(): ?TargetType
    {
        return $this->storyObject?->getTargetType() ?? $this->storyObjectType;
    }

    public function setStoryObjectType(?TargetType $storyObjectType): self
    {
        $this->storyObjectType = $storyObjectType;
        return $this;
    }
}
