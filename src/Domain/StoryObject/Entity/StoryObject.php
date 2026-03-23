<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\LarpAwareInterface;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StoryObjectRepository::class)]
#[ORM\Index(columns: ['title'])]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    TargetType::Character->value => Character::class,
    TargetType::Thread->value => Thread::class,
    TargetType::Quest->value => Quest::class,
    TargetType::Event->value => Event::class,
    TargetType::Relation->value => Relation::class,
    TargetType::Faction->value => Faction::class,
    TargetType::Item->value => Item::class,
    TargetType::Place->value => Place::class,
    TargetType::LoreDocument->value => LoreDocument::class,
])]
#[Gedmo\Loggable(logEntryClass: StoryObjectLogEntry::class)]
abstract class StoryObject implements CreatorAwareInterface, Timestampable, \App\Domain\Core\Entity\TargetableInterface, LarpAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    /*
    * The title of the story object. This is a short description of the object.
    * It should be unique within the context of the LARP - title of the vacancy, thread, quest name, etc.
    *
    */
    #[Gedmo\Versioned]
    #[ORM\Column(length: 255)]
    protected ?string $title = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp = null;

    /** @var Collection<ExternalReference>  */
    #[ORM\OneToMany(targetEntity: ExternalReference::class, mappedBy: 'storyObject', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $externalReferences;

    #[ORM\OneToMany(targetEntity: Relation::class, mappedBy: 'from')]
    private Collection $relationsFrom;

    #[ORM\OneToMany(targetEntity: Relation::class, mappedBy: 'to')]
    private Collection $relationsTo;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->externalReferences = new ArrayCollection();
        $this->relationsFrom = new ArrayCollection();
        $this->relationsTo = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
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

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getExternalReferences(): Collection
    {
        return $this->externalReferences;
    }

    public function setExternalReferences(Collection $externalReferences): void
    {
        $this->externalReferences = $externalReferences;
    }

    /** @return Collection<Relation> */
    public function getRelationsFrom(): Collection
    {
        return $this->relationsFrom;
    }

    /** @return Collection<Relation> */
    public function getRelationsTo(): Collection
    {
        return $this->relationsTo;
    }

    public function isNew(): bool
    {
        return $this->createdAt === null;
    }

    public function exists(): bool
    {
        return $this->createdAt !== null;
    }
}
