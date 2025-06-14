<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\TargetType;
use App\Entity\Trait\CreatorAwareInterface;
use App\Repository\StoryObject\LarpFactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LarpFactionRepository::class)]
class LarpFaction extends StoryObject implements CreatorAwareInterface
{
    #[ORM\ManyToMany(targetEntity: LarpCharacter::class, mappedBy: 'factions')]
    private Collection $members;

    #[ORM\ManyToMany(targetEntity: Quest::class, inversedBy: 'involvedFactions')]
    private Collection $quests;

    #[ORM\ManyToMany(targetEntity: Thread::class, inversedBy: 'involvedFactions')]
    private Collection $threads;

    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
        $this->quests = new ArrayCollection();
        $this->threads = new ArrayCollection();
    }

    public function addQuest(Quest $quest): self
    {
        if (!$this->quests->contains($quest)) {
            $this->quests->add($quest);
        }
        return $this;
    }

    public function removeQuest(Quest $quest): self
    {
        $this->quests->removeElement($quest);
        return $this;
    }

    public function addThread(Thread $thread): self
    {
        if (!$this->threads->contains($thread)) {
            $this->threads->add($thread);
        }
        return $this;
    }

    public function removeThread(Thread $thread): self
    {
        $this->threads->removeElement($thread);
        return $this;
    }

    /**
     * @return Collection<LarpCharacter>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(LarpCharacter $larpCharacter): self
    {
        if (!$this->members->contains($larpCharacter)) {
            $this->members[] = $larpCharacter;
            $larpCharacter->addFaction($this);
        }
        return $this;
    }

    public function removeMember(LarpCharacter $character): self
    {
        if ($this->members->removeElement($character)) {
            // set the owning side to null (unless already changed)
            $character->removeFaction($this);
        }
        return $this;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Faction;
    }


    public function getQuests(): Collection
    {
        return $this->quests;
    }

    public function setQuests(Collection $quests): void
    {
        $this->quests = $quests;
    }

    public function getThreads(): Collection
    {
        return $this->threads;
    }

    public function setThreads(Collection $threads): void
    {
        $this->threads = $threads;
    }
}
