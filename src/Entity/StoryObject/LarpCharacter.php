<?php

namespace App\Entity\StoryObject;

use App\Entity\Enum\CharacterType;
use App\Entity\Enum\Gender;
use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\Skill;
use App\Entity\Tag;
use App\Repository\StoryObject\LarpCharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[Gedmo\Loggable]
#[UniqueEntity(
    fields: ['larp', 'title'],
    message: 'A character with this title already exists in this LARP.'
)]
#[ORM\Index(columns: ['larp_id'])]
#[ORM\Index(columns: ['in_game_name'])]
#[ORM\Entity(repositoryClass: LarpCharacterRepository::class)]
class LarpCharacter extends StoryObject
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inGameName = null;

    #[ORM\ManyToOne(targetEntity: Larp::class, inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Larp $larp = null;

    #[ORM\OneToOne(targetEntity: self::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: "previous_character_id", referencedColumnName: "id", nullable: true)]
    private ?LarpCharacter $previousCharacter = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $postLarpFate = null;
    
    #[ORM\Column(length: 255, nullable: true, enumType: Gender::class)]
    private ?Gender $gender = null;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LarpParticipant $storyWriter = null;

    //notes - internal notes for the character
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    //type - player/long npc/short npc/ game master/ generic npc
    #[ORM\Column(enumType: CharacterType::class)]
    private CharacterType $characterType;

    //character tags - like what is to play the character many to many LarpCharacterTag

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: "larp_character_tags")]
    private Collection $tags;

    //skills - list can be defined by organizers and people responsible for larp mechanics many to many to LarpCharacterSkill
    #[ORM\OneToMany(targetEntity: LarpCharacterSkill::class, mappedBy: 'character')]
    #[ORM\JoinTable(name: "larp_character_skill")]
    private Collection $skills;

    //items that character should start the game with (each item should be defined in the system, crafted by crafters or bought by the organizers)
    #[ORM\OneToMany(targetEntity: LarpCharacterItem::class,  mappedBy: 'character')]
    #[ORM\JoinTable(name: "larp_character_item")]
    private Collection $items;

    #[ORM\ManyToMany(targetEntity: LarpFaction::class, inversedBy: 'members', cascade: ['persist'])]
    private Collection $factions;

    #[ORM\ManyToMany(targetEntity: Quest::class, inversedBy: 'involvedCharacters')]
    private Collection $quests;

    #[ORM\ManyToMany(targetEntity: Thread::class, inversedBy: 'involvedCharacters')]
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
        $this->characterType = CharacterType::Player;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): void
    {
        $this->larp = $larp;
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
     * @return Collection<LarpFaction>
     */
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(LarpFaction $larpFaction): self
    {
        if (!$this->factions->contains($larpFaction)) {
            $this->factions[] = $larpFaction;
//            $larpFaction->addMember($this);
        }
        return $this;
    }

    public function removeFaction(LarpFaction $larpFaction): self
    {
        $this->factions->removeElement($larpFaction);
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

    public static function getTargetType(): TargetType
    {
        return TargetType::Character;
    }

}
