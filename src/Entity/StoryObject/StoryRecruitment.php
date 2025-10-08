<?php

namespace App\Entity\StoryObject;

use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\StoryObject\StoryRecruitmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: StoryRecruitmentRepository::class)]
class StoryRecruitment
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?StoryObject $storyObject = null;

    #[ORM\Column(type: 'integer')]
    private int $requiredNumber = 1;

    #[ORM\Column(length: 255)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /** @var Collection<RecruitmentProposal> */
    #[ORM\OneToMany(mappedBy: 'recruitment', targetEntity: RecruitmentProposal::class, cascade: ['persist', 'remove'])]
    private Collection $proposals;

    public function __construct()
    {
        $this->proposals = new ArrayCollection();
    }

    public function getStoryObject(): ?StoryObject
    {
        return $this->storyObject;
    }

    public function setStoryObject(StoryObject $storyObject): void
    {
        $this->storyObject = $storyObject;
    }

    public function getRequiredNumber(): int
    {
        return $this->requiredNumber;
    }

    public function setRequiredNumber(int $requiredNumber): void
    {
        $this->requiredNumber = $requiredNumber;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getProposals(): Collection
    {
        return $this->proposals;
    }

    public function addProposal(RecruitmentProposal $proposal): void
    {
        if (!$this->proposals->contains($proposal)) {
            $this->proposals->add($proposal);
            $proposal->setRecruitment($this);
        }
    }

    public function removeProposal(RecruitmentProposal $proposal): void
    {
        if ($this->proposals->removeElement($proposal)) {
            if ($proposal->getRecruitment() === $this) {
                $proposal->setRecruitment(null);
            }
        }
    }
}
