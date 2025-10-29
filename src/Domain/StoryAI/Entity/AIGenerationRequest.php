<?php

declare(strict_types=1);

namespace App\Domain\StoryAI\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Infrastructure\Entity\User;
use App\Domain\StoryAI\Repository\AIGenerationRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Tracks AI generation requests for auditing and history.
 *
 * @TODO: Add status field for async request tracking (Phase 5)
 * @TODO: Add cost tracking fields (Phase 5)
 */
#[ORM\Entity(repositoryClass: AIGenerationRequestRepository::class)]
#[ORM\Table(name: 'ai_generation_request')]
class AIGenerationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $requestedBy;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $featureType; // 'gap_analysis', 'thread_suggestion', etc.

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $provider; // 'openai', 'claude', 'ollama', etc.

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $parameters = null; // Filters, tags, selected entities

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokensUsed = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $responseTimeMs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRequestedBy(): User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(User $requestedBy): self
    {
        $this->requestedBy = $requestedBy;
        return $this;
    }

    public function getFeatureType(): string
    {
        return $this->featureType;
    }

    public function setFeatureType(string $featureType): self
    {
        $this->featureType = $featureType;
        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function setParameters(?array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getTokensUsed(): ?int
    {
        return $this->tokensUsed;
    }

    public function setTokensUsed(?int $tokensUsed): self
    {
        $this->tokensUsed = $tokensUsed;
        return $this;
    }

    public function getResponseTimeMs(): ?int
    {
        return $this->responseTimeMs;
    }

    public function setResponseTimeMs(?int $responseTimeMs): self
    {
        $this->responseTimeMs = $responseTimeMs;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isSuccessful(): bool
    {
        return $this->errorMessage === null;
    }
}
