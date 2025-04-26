<?php

namespace App\Entity\StoryObject;

use App\Entity\Skill;
use App\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LarpCharacterSkill
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: LarpCharacter::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(nullable: false)]
    private LarpCharacter $character;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Skill $skill;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $description = null;

}