<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\RecruitmentProposalStatus;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\StoryObject\RecruitmentProposalRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: RecruitmentProposalRepository::class)]
class RecruitmentProposal
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: StoryRecruitment::class, inversedBy: 'proposals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryRecruitment $recruitment = null;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Character $character = null;

    #[ORM\Column(enumType: RecruitmentProposalStatus::class)]
    private RecruitmentProposalStatus $status = RecruitmentProposalStatus::PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getRecruitment(): ?StoryRecruitment
    {
        return $this->recruitment;
    }

    public function setRecruitment(?StoryRecruitment $recruitment): void
    {
        $this->recruitment = $recruitment;
    }

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setCharacter(Character $character): void
    {
        $this->character = $character;
    }

    public function getStatus(): RecruitmentProposalStatus
    {
        return $this->status;
    }

    public function setStatus(RecruitmentProposalStatus $status): void
    {
        $this->status = $status;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }
}
