<?php

namespace App\Domain\StoryObject\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CharacterItem
{
    use UuidTraitEntity;

    //gedmo loggable
    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Character $character;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    #[ORM\Column(type: 'integer')]
    private int $amount = 1;

    public function getCharacter(): Character
    {
        return $this->character;
    }

    public function setCharacter(Character $character): void
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
