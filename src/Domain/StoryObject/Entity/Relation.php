<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Repository\RelationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelationRepository::class)]
class Relation extends StoryObject
{
    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryObject $from = null;

    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryObject $to = null;

    private ?TargetType $fromType = null;
    private ?TargetType $toType = null;

    #[ORM\Column(name: 'relation_type', enumType: RelationType::class)]
    private RelationType $relationType = RelationType::Friend;


    public function getFrom(): ?StoryObject
    {
        return $this->from;
    }

    public function setFrom(?StoryObject $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function getTo(): ?StoryObject
    {
        return $this->to;
    }

    public function setTo(?StoryObject $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Relation;
    }

    public function getFromType(): ?TargetType
    {
        return $this->fromType ?? $this->getFrom()?->getTargetType();
    }

    public function setFromType(?TargetType $fromType): self
    {
        $this->fromType = $fromType;
        return $this;
    }

    public function getToType(): ?TargetType
    {
        return $this->toType ?? $this->getTo()?->getTargetType();
    }

    public function setToType(?TargetType $toType): self
    {
        $this->toType = $toType;
        return $this;
    }

    public function getRelationType(): RelationType
    {
        return $this->relationType;
    }

    public function setRelationType(RelationType $relationType): self
    {
        $this->relationType = $relationType;
        return $this;
    }
}
