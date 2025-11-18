<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Survey\Repository\SurveyQuestionOptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SurveyQuestionOptionRepository::class)]
#[ORM\Index(columns: ['question_id', 'order_position'])]
class SurveyQuestionOption
{
    use UuidTraitEntity;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $optionText = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orderPosition = 0;

    #[ORM\ManyToOne(targetEntity: SurveyQuestion::class, inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SurveyQuestion $question = null;

    /** @var Collection<SurveyAnswer> */
    #[ORM\ManyToMany(targetEntity: SurveyAnswer::class, mappedBy: 'selectedOptions')]
    private Collection $answers;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->answers = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOptionText(): ?string
    {
        return $this->optionText;
    }

    public function setOptionText(string $optionText): self
    {
        $this->optionText = $optionText;

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

    public function getQuestion(): ?SurveyQuestion
    {
        return $this->question;
    }

    public function setQuestion(?SurveyQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @return Collection<SurveyAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }
}
