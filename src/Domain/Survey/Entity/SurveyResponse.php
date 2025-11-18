<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\Survey\Repository\SurveyResponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SurveyResponseRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['survey_id'])]
#[ORM\UniqueConstraint(columns: ['larp_id', 'user_id'])]
class SurveyResponse implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'responses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Survey $survey = null;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: SubmissionStatus::class)]
    private ?SubmissionStatus $status = SubmissionStatus::NEW;

    /** @var Collection<SurveyAnswer> */
    #[ORM\OneToMany(targetEntity: SurveyAnswer::class, mappedBy: 'response', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $answers;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $matchSuggestions = null;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Character $assignedCharacter = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $organizerNotes = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->answers = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): self
    {
        $this->survey = $survey;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?SubmissionStatus
    {
        return $this->status;
    }

    public function setStatus(SubmissionStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<SurveyAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(SurveyAnswer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setResponse($this);
        }

        return $this;
    }

    public function removeAnswer(SurveyAnswer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            if ($answer->getResponse() === $this) {
                $answer->setResponse(null);
            }
        }

        return $this;
    }

    public function getMatchSuggestions(): ?array
    {
        return $this->matchSuggestions;
    }

    public function setMatchSuggestions(?array $matchSuggestions): self
    {
        $this->matchSuggestions = $matchSuggestions;

        return $this;
    }

    public function getAssignedCharacter(): ?Character
    {
        return $this->assignedCharacter;
    }

    public function setAssignedCharacter(?Character $assignedCharacter): self
    {
        $this->assignedCharacter = $assignedCharacter;

        return $this;
    }

    public function getOrganizerNotes(): ?string
    {
        return $this->organizerNotes;
    }

    public function setOrganizerNotes(?string $organizerNotes): self
    {
        $this->organizerNotes = $organizerNotes;

        return $this;
    }
}
