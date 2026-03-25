<?php

declare(strict_types=1);

namespace App\Domain\Application\Entity;

use App\Domain\Application\Repository\LarpApplicationTurnRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LarpApplicationTurnRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
class LarpApplicationTurn implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Larp $larp;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $roundNumber;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $opensAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closesAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): static
    {
        $this->larp = $larp;

        return $this;
    }

    public function getRoundNumber(): int
    {
        return $this->roundNumber;
    }

    public function setRoundNumber(int $roundNumber): static
    {
        $this->roundNumber = $roundNumber;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getOpensAt(): ?\DateTimeInterface
    {
        return $this->opensAt;
    }

    public function setOpensAt(?\DateTimeInterface $opensAt): static
    {
        $this->opensAt = $opensAt;

        return $this;
    }

    public function getClosesAt(): ?\DateTimeInterface
    {
        return $this->closesAt;
    }

    public function setClosesAt(?\DateTimeInterface $closesAt): static
    {
        $this->closesAt = $closesAt;

        return $this;
    }

    public function isOpen(): bool
    {
        $now = new \DateTimeImmutable();

        if ($this->opensAt !== null && $now < $this->opensAt) {
            return false;
        }

        if ($this->closesAt !== null && $now > $this->closesAt) {
            return false;
        }

        return true;
    }
}
