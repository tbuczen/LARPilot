<?php

namespace App\Domain\CallTranscript\Entity;

use App\Domain\CallTranscript\Repository\CallTranscriptSettingsRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CallTranscriptSettingsRepository::class)]
#[ORM\Table(name: 'call_transcript_settings')]
class CallTranscriptSettings
{
    use UuidTraitEntity;

    #[ORM\OneToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Larp $larp;

    #[ORM\Column(options: ['default' => true])]
    private bool $summaryEnabled = true;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $discordChannelId = null;

    public function __construct(Larp $larp)
    {
        $this->id = Uuid::v4();
        $this->larp = $larp;
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function isSummaryEnabled(): bool
    {
        return $this->summaryEnabled;
    }

    public function setSummaryEnabled(bool $summaryEnabled): self
    {
        $this->summaryEnabled = $summaryEnabled;
        return $this;
    }

    public function getDiscordChannelId(): ?string
    {
        return $this->discordChannelId;
    }

    public function setDiscordChannelId(?string $discordChannelId): self
    {
        $this->discordChannelId = $discordChannelId;
        return $this;
    }
}
