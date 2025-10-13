<?php

namespace App\Domain\Kanban\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Kanban\Entity\Enum\KanbanStatus;
use App\Domain\Kanban\Entity\Enum\TaskVisibility;
use App\Domain\Kanban\Repository\KanbanTaskRepository;
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

    #[ORM\Column(type: 'string', enumType: TaskVisibility::class, options: [
        'default' => TaskVisibility::ALL
    ])]
    private TaskVisibility $visibility = TaskVisibility::ALL;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpParticipant $assignedTo = null;

    #[ORM\Column(type: 'integer')]
    private int $priority = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $activityLog = [];

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
        // Log status change
        $this->logActivity('status_changed', [
            'old_status' => $this->status->value,
            'new_status' => $status->value,
            'timestamp' => new \DateTimeImmutable()
        ]);

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

    public function getAssignedTo(): ?LarpParticipant
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?LarpParticipant $assignedTo): self
    {
        // Log assignment change
        if ($this->assignedTo !== $assignedTo) {
            $this->logActivity('assignment_changed', [
                'old_assignee' => $this->assignedTo?->getName(),
                'new_assignee' => $assignedTo?->getName(),
                'timestamp' => new \DateTimeImmutable()
            ]);
        }

        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getVisibility(): TaskVisibility
    {
        return $this->visibility;
    }

    public function setVisibility(TaskVisibility $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function getActivityLog(): array
    {
        return $this->activityLog ?? [];
    }

    private function logActivity(string $type, array $data): void
    {
        if ($this->activityLog === null) {
            $this->activityLog = [];
        }

        $this->activityLog[] = [
            'type' => $type,
            'data' => $data,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
    }
}
