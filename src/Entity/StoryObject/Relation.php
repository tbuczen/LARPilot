<?php

namespace App\Entity\StoryObject;

use App\Entity\Larp;
use App\Repository\StoryObject\RelationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Enum\TargetType;

#[ORM\Entity(repositoryClass: RelationRepository::class)]

class Relation extends StoryObject
{

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryObject $from = null;

    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryObject $to = null;

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
    }

    public function getFrom(): ?StoryObject
    {
        return $this->from;
    }

    public function setFrom(?StoryObject $from): void
    {
        $this->from = $from;
    }

    public function getTo(): ?StoryObject
    {
        return $this->to;
    }

    public function setTo(?StoryObject $to): void
    {
        $this->to = $to;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public static function getTargetType(): TargetType
    {
       return TargetType::Relation;
    }
}