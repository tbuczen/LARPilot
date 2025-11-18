<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Survey\Entity\Enum\SurveyQuestionType;
use App\Domain\Survey\Repository\SurveyQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SurveyQuestionRepository::class)]
#[ORM\Index(columns: ['survey_id', 'order_position'])]
class SurveyQuestion
{
    use UuidTraitEntity;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $questionText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $helpText = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: SurveyQuestionType::class)]
    private ?SurveyQuestionType $questionType = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isRequired = true;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orderPosition = 0;

    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Survey $survey = null;

    /** @var Collection<SurveyQuestionOption> */
    #[ORM\OneToMany(targetEntity: SurveyQuestionOption::class, mappedBy: 'question', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['orderPosition' => 'ASC'])]
    private Collection $options;

    /** @var Collection<SurveyAnswer> */
    #[ORM\OneToMany(targetEntity: SurveyAnswer::class, mappedBy: 'question')]
    private Collection $answers;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $tagCategory = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->options = new ArrayCollection();
        $this->answers = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): self
    {
        $this->questionText = $questionText;

        return $this;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): self
    {
        $this->helpText = $helpText;

        return $this;
    }

    public function getQuestionType(): ?SurveyQuestionType
    {
        return $this->questionType;
    }

    public function setQuestionType(SurveyQuestionType $questionType): self
    {
        $this->questionType = $questionType;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    public function getOrderPosition(): int
    {
        return $this->orderPosition;
    }

    public function setOrderPosition(int $orderPosition): self
    {
        $this->orderPosition = $orderPosition;

        return $this;
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

    /**
     * @return Collection<SurveyQuestionOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(SurveyQuestionOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setQuestion($this);
        }

        return $this;
    }

    public function removeOption(SurveyQuestionOption $option): self
    {
        if ($this->options->removeElement($option)) {
            if ($option->getQuestion() === $this) {
                $option->setQuestion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<SurveyAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function getTagCategory(): ?string
    {
        return $this->tagCategory;
    }

    public function setTagCategory(?string $tagCategory): self
    {
        $this->tagCategory = $tagCategory;

        return $this;
    }
}
