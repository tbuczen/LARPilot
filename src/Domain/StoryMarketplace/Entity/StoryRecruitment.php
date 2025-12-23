<?php

namespace App\Domain\StoryMarketplace\Entity;

use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\StoryMarketplace\Repository\StoryRecruitmentRepository;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: StoryRecruitmentRepository::class)]
class StoryRecruitment implements CreatorAwareInterface
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
    #[ORM\OneToMany(targetEntity: RecruitmentProposal::class, mappedBy: 'recruitment', cascade: ['persist', 'remove'])]
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
        if ($this->proposals->removeElement($proposal) && $proposal->getRecruitment() === $this) {
            $proposal->setRecruitment(null);
        }
    }
}
