<?php

namespace App\Entity;

use App\Entity\Enum\KanbanStatus;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\KanbanTaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: KanbanTaskRepository::class)]
class KanbanTask
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: KanbanStatus::class)]
    private KanbanStatus $status = KanbanStatus::TODO;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): KanbanStatus
    {
        return $this->status;
    }

    public function setStatus(KanbanStatus $status): void
    {
        $this->status = $status;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
