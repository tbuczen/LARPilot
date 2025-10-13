<?php

namespace App\Domain\Application\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Repository\LarpApplicationVoteRepository;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: LarpApplicationVoteRepository::class)]
#[ORM\Table(name: 'larp_application_vote')]
#[ORM\Index(columns: ['choice_id'])]
#[ORM\Index(columns: ['user_id'])]
#[ORM\UniqueConstraint(columns: ['choice_id', 'user_id'])]
class LarpApplicationVote
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: LarpApplicationChoice::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LarpApplicationChoice $choice;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $vote; // 1 for upvote, -1 for downvote

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $justification = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getChoice(): LarpApplicationChoice
    {
        return $this->choice;
    }

    public function setChoice(LarpApplicationChoice $choice): void
    {
        $this->choice = $choice;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User|UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getVote(): int
    {
        return $this->vote;
    }

    public function setVote(int $vote): void
    {
        $this->vote = $vote;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): void
    {
        $this->justification = $justification;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function isUpvote(): bool
    {
        return $this->vote > 0;
    }

    public function isDownvote(): bool
    {
        return $this->vote < 0;
    }
}
