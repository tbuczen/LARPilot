<?php

namespace App\Entity;

use App\Entity\StoryObject\LarpCharacter;
use App\Entity\Trait\UuidTraitEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['application_id'])]
#[ORM\Index(columns: ['character_id'])]
class LarpApplicationChoice
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: LarpApplication::class, inversedBy: 'choices')]
    #[ORM\JoinColumn(nullable: false)]
    private LarpApplication $application;

    #[ORM\ManyToOne(targetEntity: LarpCharacter::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LarpCharacter $character;

    #[ORM\Column(type: 'integer')]
    private int $priority = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $justification = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $visual = null;

    #[ORM\Column(type: 'integer')]
    private int $votes = 0;

    public function getApplication(): LarpApplication
    {
        return $this->application;
    }

    public function setApplication(LarpApplication $application): void
    {
        $this->application = $application;
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

    public function getVotes(): int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): void
    {
        $this->votes = $votes;
    }
}
