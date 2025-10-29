<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity;

use App\Domain\StoryAI\Repository\AIGenerationResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Stores AI-generated content with metadata.
 *
 * @TODO: Add user rating field for feedback loop (Phase 5)
 */
#[ORM\Entity(repositoryClass: AIGenerationResultRepository::class)]
#[ORM\Table(name: 'ai_generation_result')]
class AIGenerationResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AIGenerationRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AIGenerationRequest $request;

    #[ORM\Column(type: Types::JSON)]
    private array $content; // Structured AI response

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $accepted = false; // Did user accept/use this suggestion?

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $createdEntityType = null; // 'thread', 'quest', 'event', etc.

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $createdEntityId = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequest(): AIGenerationRequest
    {
        return $this->request;
    }

    public function setRequest(AIGenerationRequest $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): self
    {
        $this->accepted = $accepted;
        return $this;
    }

    public function getCreatedEntityType(): ?string
    {
        return $this->createdEntityType;
    }

    public function setCreatedEntityType(?string $createdEntityType): self
    {
        $this->createdEntityType = $createdEntityType;
        return $this;
    }

    public function getCreatedEntityId(): ?int
    {
        return $this->createdEntityId;
    }

    public function setCreatedEntityId(?int $createdEntityId): self
    {
        $this->createdEntityId = $createdEntityId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsAccepted(string $entityType, int $entityId): self
    {
        $this->accepted = true;
        $this->createdEntityType = $entityType;
        $this->createdEntityId = $entityId;
        return $this;
    }
}
