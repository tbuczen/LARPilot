<?php

namespace App\Entity\StoryObject;

use App\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LarpCharacterItem
{
    use UuidTraitEntity;

    //gedmo loggable
    #[ORM\ManyToOne(targetEntity: LarpCharacter::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private LarpCharacter $character;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    #[ORM\Column(type: 'integer')]
    private int $amount = 1;

    public function getCharacter(): LarpCharacter
    {
        return $this->character;
    }

    public function setCharacter(LarpCharacter $character): void
    {
        $this->character = $character;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }
}
