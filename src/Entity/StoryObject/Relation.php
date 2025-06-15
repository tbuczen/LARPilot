<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Enum\RelationType;
use App\Entity\Larp;
use App\Repository\StoryObject\RelationRepository;
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
    private RelationType $type = RelationType::Friend;


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

    public function getType(): RelationType
    {
        return $this->type;
    }

    public function setType(RelationType $type): self
    {
        $this->type = $type;
        return $this;
    }
}
