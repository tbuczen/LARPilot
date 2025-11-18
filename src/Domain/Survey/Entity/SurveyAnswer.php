<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity;

use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Survey\Repository\SurveyAnswerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SurveyAnswerRepository::class)]
#[ORM\Index(columns: ['response_id'])]
#[ORM\Index(columns: ['question_id'])]
class SurveyAnswer
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: SurveyResponse::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SurveyResponse $response = null;

    #[ORM\ManyToOne(targetEntity: SurveyQuestion::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SurveyQuestion $question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answerText = null;

    /** @var Collection<SurveyQuestionOption> */
    #[ORM\ManyToMany(targetEntity: SurveyQuestionOption::class, inversedBy: 'answers')]
    #[ORM\JoinTable(name: 'survey_answer_selected_options')]
    private Collection $selectedOptions;

    /** @var Collection<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'survey_answer_selected_tags')]
    private Collection $selectedTags;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->selectedOptions = new ArrayCollection();
        $this->selectedTags = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getResponse(): ?SurveyResponse
    {
        return $this->response;
    }

    public function setResponse(?SurveyResponse $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getQuestion(): ?SurveyQuestion
    {
        return $this->question;
    }

    public function setQuestion(?SurveyQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswerText(): ?string
    {
        return $this->answerText;
    }

    public function setAnswerText(?string $answerText): self
    {
        $this->answerText = $answerText;

        return $this;
    }

    /**
     * @return Collection<SurveyQuestionOption>
     */
    public function getSelectedOptions(): Collection
    {
        return $this->selectedOptions;
    }

    public function addSelectedOption(SurveyQuestionOption $selectedOption): self
    {
        if (!$this->selectedOptions->contains($selectedOption)) {
            $this->selectedOptions->add($selectedOption);
        }

        return $this;
    }

    public function removeSelectedOption(SurveyQuestionOption $selectedOption): self
    {
        $this->selectedOptions->removeElement($selectedOption);

        return $this;
    }

    /**
     * @return Collection<Tag>
     */
    public function getSelectedTags(): Collection
    {
        return $this->selectedTags;
    }

    public function addSelectedTag(Tag $selectedTag): self
    {
        if (!$this->selectedTags->contains($selectedTag)) {
            $this->selectedTags->add($selectedTag);
        }

        return $this;
    }

    public function removeSelectedTag(Tag $selectedTag): self
    {
        $this->selectedTags->removeElement($selectedTag);

        return $this;
    }
}
