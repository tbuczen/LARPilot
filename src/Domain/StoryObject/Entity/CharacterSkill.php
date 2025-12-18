<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CharacterSkill
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(nullable: false)]
    private Character $character;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Skill $skill;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $description = null;

    public function getCharacter(): Character
    {
        return $this->character;
    }

    public function setCharacter(Character $character): void
    {
        $this->character = $character;
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): void
    {
        $this->skill = $skill;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
