<?php

namespace App\Entity;

use App\Entity\StoryObject\LarpCharacter;
use App\Entity\Trait\UuidTraitEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['submission_id'])]
#[ORM\Index(columns: ['character_id'])]
class LarpCharacterSubmissionChoice
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: LarpCharacterSubmission::class, inversedBy: 'choices')]
    #[ORM\JoinColumn(nullable: false)]
    private LarpCharacterSubmission $submission;

    #[ORM\ManyToOne(targetEntity: LarpCharacter::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LarpCharacter $character;

    #[ORM\Column(type: 'integer')]
    private int $priority = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $justification = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $visual = null;

    public function getSubmission(): LarpCharacterSubmission
    {
        return $this->submission;
    }

    public function setSubmission(LarpCharacterSubmission $submission): void
    {
        $this->submission = $submission;
    }

    public function getCharacter(): LarpCharacter
    {
        return $this->character;
    }

    public function setCharacter(LarpCharacter $character): void
    {
        $this->character = $character;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): void
    {
        $this->justification = $justification;
    }

    public function getVisual(): ?string
    {
        return $this->visual;
    }

    public function setVisual(?string $visual): void
    {
        $this->visual = $visual;
    }
}
