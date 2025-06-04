<?php

namespace App\Entity;

use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Enum\ReferenceRole;
use App\Entity\Enum\ReferenceType;
use App\Entity\Enum\TargetType;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\Thread;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\ExternalReferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[Gedmo\Loggable]
#[ORM\Entity(repositoryClass: ExternalReferenceRepository::class)]
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
