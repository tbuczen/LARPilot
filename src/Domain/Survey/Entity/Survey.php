<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Survey\Repository\SurveyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SurveyRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
class Survey implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToOne(inversedBy: 'survey', targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    /** @var Collection<SurveyQuestion> */
    #[ORM\OneToMany(targetEntity: SurveyQuestion::class, mappedBy: 'survey', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['orderPosition' => 'ASC'])]
    private Collection $questions;

    /** @var Collection<SurveyResponse> */
    #[ORM\OneToMany(targetEntity: SurveyResponse::class, mappedBy: 'survey', cascade: ['persist'])]
    private Collection $responses;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = false;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->questions = new ArrayCollection();
        $this->responses = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): self
    {
        $this->larp = $larp;

        return $this;
    }

    /**
     * @return Collection<SurveyQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(SurveyQuestion $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setSurvey($this);
        }

        return $this;
    }

    public function removeQuestion(SurveyQuestion $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getSurvey() === $this) {
                $question->setSurvey(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<SurveyResponse>
     */
    public function getResponses(): Collection
    {
        return $this->responses;
    }

    public function addResponse(SurveyResponse $response): self
    {
        if (!$this->responses->contains($response)) {
            $this->responses->add($response);
            $response->setSurvey($this);
        }

        return $this;
    }

    public function removeResponse(SurveyResponse $response): self
    {
        if ($this->responses->removeElement($response)) {
            if ($response->getSurvey() === $this) {
                $response->setSurvey(null);
            }
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }
}
