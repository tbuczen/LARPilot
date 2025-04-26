<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\ExternalReference;
use App\Entity\Larp;
use App\Entity\TargetableInterface;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\LarpAwareInterface;
use App\Entity\Trait\UuidTraitEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    TargetType::Character->value => LarpCharacter::class,
    TargetType::Thread->value  => Thread::class,
    TargetType::Quest->value  => Quest::class,
    TargetType::Event->value  => Event::class,
    TargetType::Relation->value  => Relation::class,
    TargetType::Faction->value  => LarpFaction::class,
    TargetType::Item->value  => Item::class,
])]
abstract class StoryObject implements CreatorAwareInterface, Timestampable, TargetableInterface, LarpAwareInterface
{

    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    /*
    * The title of the story object. This is a short description of the object.
    * It should be unique within the context of the LARP - title of the vacancy, thread, quest name, etc.
    *
    */
    #[ORM\Column(length: 255)]
    protected ?string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    /** @var Collection<ExternalReference>  */
    #[ORM\OneToMany(targetEntity: ExternalReference::class, mappedBy: 'storyObject', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $externalReferences;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->externalReferences = new ArrayCollection();
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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getExternalReferences(): Collection
    {
        return $this->externalReferences;
    }

    public function setExternalReferences(Collection $externalReferences): void
    {
        $this->externalReferences = $externalReferences;
    }

}