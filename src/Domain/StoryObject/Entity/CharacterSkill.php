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
}
