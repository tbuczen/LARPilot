<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Repository\StoryObject\ItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Money\Money;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item extends StoryObject
{
    /** @var StoryObject|null The item can be for specific quest, thread, character, faction */
    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: true)]
    protected ?StoryObject $designation = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isCrafted = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isPurchased = false;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 1;

    #[Embedded]
    private Money $cost;


    public function isCrafted(): bool
    {
        return $this->isCrafted;
    }

    public function setIsCrafted(bool $isCrafted): void
    {
        $this->isCrafted = $isCrafted;
    }

    public function getDesignation(): ?StoryObject
    {
        return $this->designation;
    }

    public function setDesignation(?StoryObject $designation): void
    {
        $this->designation = $designation;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function isPurchased(): bool
    {
        return $this->isPurchased;
    }

    public function setIsPurchased(bool $isPurchased): void
    {
        $this->isPurchased = $isPurchased;
    }

    public function getCost(): Money
    {
        return $this->cost;
    }

    public function setCost(Money $cost): void
    {
        $this->cost = $cost;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Item;
    }
}
