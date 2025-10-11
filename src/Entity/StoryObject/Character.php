<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\CharacterType;
use App\Entity\Enum\Gender;
use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\Skill;
use App\Entity\Tag;
use App\Repository\StoryObject\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(
    fields: ['larp', 'title'],
    message: 'A character with this title already exists in this LARP.'
)]
#[ORM\Index(columns: ['in_game_name'])]
#[ORM\Entity(repositoryClass: CharacterRepository::class)]
class Character extends StoryObject
{
    #[Gedmo\Versioned]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inGameName = null;

    #[ORM\OneToOne(targetEntity: self::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: "previous_character_id", referencedColumnName: "id", nullable: true)]
    private ?Character $previousCharacter = null;

    #[ORM\OneToOne(targetEntity: self::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: "continuation_character_id", referencedColumnName: "id", nullable: true)]
    private ?Character $continuation = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $postLarpFate = null;
    
    #[Gedmo\Versioned]
    #[ORM\Column(length: 255, nullable: true, enumType: Gender::class)]
    private ?Gender $gender = null;

    #[Gedmo\Versioned]
    #[ORM\Column(length: 255, nullable: true, enumType: Gender::class)]
    private ?Gender $preferredGender = null;

    #[ORM\Column(type: 'boolean')]
    private bool $availableForRecruitment = false;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpParticipant $storyWriter = null;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class, inversedBy: 'larpCharacters')]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpParticipant $larpParticipant = null;

    //notes - internal notes for the character
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    //type - player/long npc/short npc/ game master/ generic npc
    #[ORM\Column(enumType: CharacterType::class)]
    private CharacterType $characterType = CharacterType::Player;

    //character tags - like what is to play the character many to many LarpCharacterTag

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: "character_tags")]
    private Collection $tags;

    //skills - list can be defined by organizers and people responsible for larp mechanics many to many to CharacterSkill
    #[ORM\OneToMany(targetEntity: CharacterSkill::class, mappedBy: 'character')]
    #[ORM\JoinTable(name: "character_skill")]
    private Collection $skills;

    //items that character should start the game with (each item should be defined in the system, crafted by crafters or bought by the organizers)
    #[ORM\OneToMany(targetEntity: CharacterItem::class, mappedBy: 'character')]
    #[ORM\JoinTable(name: "character_item")]
    private Collection $items;

    #[ORM\ManyToMany(targetEntity: Faction::class, inversedBy: 'members', cascade: ['persist'])]
    private Collection $factions;

    #[ORM\ManyToMany(targetEntity: Quest::class, mappedBy: 'involvedCharacters')]
    private Collection $quests;

    #[ORM\ManyToMany(targetEntity: Thread::class, mappedBy: 'involvedCharacters')]
    private Collection $threads;

    public function __construct()
    {
        parent::__construct();
        $this->factions = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->quests = new ArrayCollection();
        $this->threads = new ArrayCollection();
        $this->items = new ArrayCollection();
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

    public function getPreviousCharacter(): ?self
    {
        return $this->previousCharacter;
    }

    public function setPreviousCharacter(?self $previousCharacter): static
    {
        $this->previousCharacter = $previousCharacter;
        return $this;
    }

    public function getContinuation(): ?self
    {
        return $this->continuation;
    }

    public function setContinuation(?self $continuation): static
    {
        $this->continuation = $continuation;
        return $this;
    }

    public function getPostLarpFate(): ?string
    {
        return $this->postLarpFate;
    }

    public function setPostLarpFate(?string $postLarpFate): static
    {
        $this->postLarpFate = $postLarpFate;
        return $this;
    }

    /**
     * @return Collection<Faction>
     */
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(Faction $faction): self
    {
        if (!$this->factions->contains($faction)) {
            $this->factions[] = $faction;
            //            $faction->addMember($this);
        }
        return $this;
    }

    public function removeFaction(Faction $faction): self
    {
        $this->factions->removeElement($faction);
        return $this;
    }

    public function getInGameName(): ?string
    {
        return $this->inGameName;
    }

    public function setInGameName(?string $inGameName): void
    {
        $this->inGameName = $inGameName;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getPreferredGender(): ?Gender
    {
        return $this->preferredGender;
    }

    public function setPreferredGender(?Gender $preferredGender): self
    {
        $this->preferredGender = $preferredGender;
        return $this;
    }

    public function isAvailableForRecruitment(): bool
    {
        return $this->availableForRecruitment;
    }

    public function setAvailableForRecruitment(bool $availableForRecruitment): void
    {
        $this->availableForRecruitment = $availableForRecruitment;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }
        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        $this->skills->removeElement($skill);
        return $this;
    }

    public function getStoryWriter(): ?LarpParticipant
    {
        return $this->storyWriter;
    }

    public function setStoryWriter(?LarpParticipant $participant): self
    {
        $this->storyWriter = $participant;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCharacterType(): CharacterType
    {
        return $this->characterType;
    }

    public function setCharacterType(CharacterType $characterType): self
    {
        $this->characterType = $characterType;
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }
        return $this;
    }

    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);
        return $this;
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

    public function getLarpParticipant(): ?LarpParticipant
    {
        return $this->larpParticipant;
    }

    public function setLarpParticipant(?LarpParticipant $larpParticipant): void
    {
        $this->larpParticipant = $larpParticipant;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Character;
    }

    public function belongsToFaction(Faction $faction): bool
    {
        return $this->factions->contains($faction);
    }
}
