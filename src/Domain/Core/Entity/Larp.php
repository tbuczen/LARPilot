<?php

namespace App\Domain\Core\Entity;

use App\Domain\Core\Entity\Enum\LarpCharacterSystem;
use App\Domain\Core\Entity\Enum\LarpSetting;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\LarpType;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Mailing\Entity\MailTemplate;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Faction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LarpRepository::class)]
class Larp implements Timestampable, CreatorAwareInterface, \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $description;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'larps')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $location = null;

    #[ORM\Column(length: 255)]
    private ?LarpStageStatus $status = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $maxCharacterChoices = 1;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 3])]
    private int $minThreadsPerCharacter = 3;

    #[ORM\Column(nullable: true, enumType: LarpSetting::class)]
    private ?LarpSetting $setting = null;

    #[ORM\Column(nullable: true, enumType: LarpType::class)]
    private ?LarpType $type = null;

    #[ORM\Column(nullable: true, enumType: LarpCharacterSystem::class)]
    private ?LarpCharacterSystem $characterSystem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $discordServerUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookEventUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $headerImage = null;

    /** @var Collection<Character> */
    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'larp')]
    private Collection $characters;

    /** @var Collection<\App\Domain\Application\Entity\LarpApplication> */
    #[ORM\OneToMany(targetEntity: \App\Domain\Application\Entity\LarpApplication::class, mappedBy: 'larp')]
    private Collection $applications;

    /** @var Collection<LarpParticipant> */
    #[ORM\OneToMany(targetEntity: LarpParticipant::class, mappedBy: 'larp')]
    private Collection $larpParticipants;

    /** @var Collection<Event> */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'larp')]
    private Collection $events;

    /** @var Collection<\App\Domain\StoryObject\Entity\Skill> */
    #[ORM\OneToMany(targetEntity: \App\Domain\StoryObject\Entity\Skill::class, mappedBy: 'larp')]
    private Collection $skills;

    /** @var Collection<\App\Domain\Integrations\Entity\LarpIntegration> */
    #[ORM\OneToMany(targetEntity: \App\Domain\Integrations\Entity\LarpIntegration::class, mappedBy: 'larp')]
    private Collection $integrations;

    /** @var Collection<Faction> */
    #[ORM\OneToMany(targetEntity: Faction::class, mappedBy: 'larp')]
    private Collection $factions;

    /** @var Collection<\App\Domain\Gallery\Entity\Gallery> */
    #[ORM\OneToMany(targetEntity: \App\Domain\Gallery\Entity\Gallery::class, mappedBy: 'larp')]
    private Collection $galleries;

    /** @var Collection<MailTemplate> */
    #[ORM\OneToMany(targetEntity: MailTemplate::class, mappedBy: 'larp', orphanRemoval: true)]
    private Collection $mailTemplates;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->characters = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->larpParticipants = new ArrayCollection();
        $this->factions = new ArrayCollection();
        $this->integrations = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->galleries = new ArrayCollection();
        $this->mailTemplates = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getStatus(): ?LarpStageStatus
    {
        return $this->status;
    }

    public function setStatus(LarpStageStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getMaxCharacterChoices(): int
    {
        return $this->maxCharacterChoices;
    }

    public function setMaxCharacterChoices(int $maxCharacterChoices): static
    {
        $this->maxCharacterChoices = $maxCharacterChoices;
        return $this;
    }

    public function getMinThreadsPerCharacter(): int
    {
        return $this->minThreadsPerCharacter;
    }

    public function setMinThreadsPerCharacter(int $minThreadsPerCharacter): static
    {
        $this->minThreadsPerCharacter = $minThreadsPerCharacter;
        return $this;
    }

    public function getSetting(): ?LarpSetting
    {
        return $this->setting;
    }

    public function setSetting(?LarpSetting $setting): static
    {
        $this->setting = $setting;
        return $this;
    }

    public function getType(): ?LarpType
    {
        return $this->type;
    }

    public function setType(?LarpType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCharacterSystem(): ?LarpCharacterSystem
    {
        return $this->characterSystem;
    }

    public function setCharacterSystem(?LarpCharacterSystem $characterSystem): static
    {
        $this->characterSystem = $characterSystem;
        return $this;
    }

    public function getDuration(): int
    {
        if (!$this->startDate instanceof \DateTimeInterface || !$this->endDate instanceof \DateTimeInterface) {
            return 0;
        }
        
        return $this->endDate->diff($this->startDate)->days + 1;
    }

    public function getCoordinates(): ?array
    {
        return $this->location?->getCoordinates();
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $character->setLarp($this);
        }
        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        if ($this->characters->removeElement($character) && $character->getLarp() === $this) {
            $character->setLarp(null);
        }
        return $this;
    }

    /**
     * @return Collection<Faction>
     */
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(Faction $element): self
    {
        if (!$this->factions->contains($element)) {
            $this->factions[] = $element;
            $element->setLarp($this);
        }
        return $this;
    }

    public function removeFaction(Faction $element): self
    {
        if ($this->factions->removeElement($element)) {
            $element->setLarp(null);
        }
        return $this;
    }

    public function addParticipant(LarpParticipant $element): self
    {
        if (!$this->larpParticipants->contains($element)) {
            $this->larpParticipants[] = $element;
            $element->setLarp($this);
        }
        return $this;
    }

    public function removeParticipant(LarpParticipant $element): self
    {
        if ($this->larpParticipants->removeElement($element)) {
            $element->setLarp(null);
        }
        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->larpParticipants;
    }

    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function setApplications(Collection $applications): void
    {
        $this->applications = $applications;
    }

    public function getLarpParticipants(): Collection
    {
        return $this->larpParticipants;
    }

    public function setLarpParticipants(Collection $larpParticipants): void
    {
        $this->larpParticipants = $larpParticipants;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function setEvents(Collection $events): void
    {
        $this->events = $events;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function setSkills(Collection $skills): void
    {
        $this->skills = $skills;
    }

    public function getIntegrations(): Collection
    {
        return $this->integrations;
    }

    public function setIntegrations(Collection $integrations): void
    {
        $this->integrations = $integrations;
    }

    public function getMarking(): string
    {
        return $this->status?->value ?? LarpStageStatus::DRAFT->value;
    }

    public function setMarking(string $marking): void
    {
        $this->status = LarpStageStatus::from($marking);
    }

    public function getDiscordServerUrl(): ?string
    {
        return $this->discordServerUrl;
    }

    public function setDiscordServerUrl(?string $discordServerUrl): static
    {
        $this->discordServerUrl = $discordServerUrl;
        return $this;
    }

    public function getFacebookEventUrl(): ?string
    {
        return $this->facebookEventUrl;
    }

    public function setFacebookEventUrl(?string $facebookEventUrl): static
    {
        $this->facebookEventUrl = $facebookEventUrl;
        return $this;
    }

    public function getHeaderImage(): ?string
    {
        return $this->headerImage;
    }

    public function setHeaderImage(?string $headerImage): static
    {
        $this->headerImage = $headerImage;
        return $this;
    }

    public function getHeaderImagePath(): ?string
    {
        if (!$this->headerImage) {
            return null;
        }
        return '/uploads/larps/' . $this->headerImage;
    }

    /**
     * @return Collection<\App\Domain\Gallery\Entity\Gallery>
     */
    public function getGalleries(): Collection
    {
        return $this->galleries;
    }

    public function addGallery(\App\Domain\Gallery\Entity\Gallery $gallery): static
    {
        if (!$this->galleries->contains($gallery)) {
            $this->galleries->add($gallery);
            $gallery->setLarp($this);
        }
        return $this;
    }

    public function removeGallery(\App\Domain\Gallery\Entity\Gallery $gallery): static
    {
        if ($this->galleries->removeElement($gallery) && $gallery->getLarp() === $this) {
            $gallery->setLarp(null);
        }
        return $this;
    }

    /**
     * @return Collection<MailTemplate>
     */
    public function getMailTemplates(): Collection
    {
        return $this->mailTemplates;
    }

    public function addMailTemplate(MailTemplate $mailTemplate): static
    {
        if (!$this->mailTemplates->contains($mailTemplate)) {
            $this->mailTemplates->add($mailTemplate);
            $mailTemplate->setLarp($this);
        }

        return $this;
    }

    public function removeMailTemplate(MailTemplate $mailTemplate): static
    {
        $this->mailTemplates->removeElement($mailTemplate);

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
