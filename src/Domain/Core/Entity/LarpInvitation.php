<?php

namespace App\Domain\Core\Entity;

use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Core\Repository\LarpInvitationRepository;
use App\Domain\StoryObject\Entity\Character;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: LarpInvitationRepository::class)]
#[ORM\Index(columns: ['larp_id'])]
class LarpInvitation
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column(enumType: ParticipantRole::class)]
    private ParticipantRole $invitedRole;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    private ?Character $larpCharacter = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isReusable = true;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $acceptedByUserIds = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->code = bin2hex(random_bytes(16));
    }


    public function isReusable(): bool
    {
        return $this->isReusable;
    }

    public function setIsReusable(bool $isReusable): self
    {
        $this->isReusable = $isReusable;
        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getAcceptedByUserIds(): ?array
    {
        return $this->acceptedByUserIds;
    }

    public function setAcceptedByUserIds(?array $acceptedByUserIds = []): self
    {
        $this->acceptedByUserIds = $acceptedByUserIds;
        return $this;
    }

    public function addAcceptedByUserId(string $userId): self
    {
        if (!in_array($userId, $this->acceptedByUserIds, true)) {
            $this->acceptedByUserIds[] = $userId;
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(?\DateTimeImmutable $validTo = null): self
    {
        $this->validTo = $validTo;
        return $this;
    }

    public function getLarpCharacter(): ?Character
    {
        return $this->larpCharacter;
    }

    public function setLarpCharacter(?Character $larpCharacter): self
    {
        $this->larpCharacter = $larpCharacter;
        return $this;
    }

    public function getInvitedRole(): ParticipantRole
    {
        return $this->invitedRole;
    }

    public function setInvitedRole(ParticipantRole $invitedRole): void
    {
        $this->invitedRole = $invitedRole;
    }
}
