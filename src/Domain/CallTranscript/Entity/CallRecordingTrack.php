<?php

namespace App\Domain\CallTranscript\Entity;

use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Entity\Enum\CallTrackStatus;
use App\Domain\CallTranscript\Repository\CallRecordingTrackRepository;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CallRecordingTrackRepository::class)]
#[ORM\Table(name: 'call_recording_track')]
class CallRecordingTrack
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: CallRecording::class, inversedBy: 'tracks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CallRecording $recording;

    #[ORM\Column(length: 255)]
    private string $speaker;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audioFilename;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transcriptFilename;

    #[ORM\Column(length: 20, enumType: CallTrackStatus::class)]
    private CallTrackStatus $status = CallTrackStatus::PENDING;

    /**
     * @var list<array{start: float, text: string}>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $segments = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $discordUserId = null;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?LarpParticipant $participant = null;

    public function __construct(CallRecording $recording, string $speaker, ?string $audioFilename, ?string $transcriptFilename)
    {
        $this->id = Uuid::v4();
        $this->recording = $recording;
        $this->speaker = $speaker;
        $this->audioFilename = $audioFilename;
        $this->transcriptFilename = $transcriptFilename;
        $recording->addTrack($this);
    }

    public function getRecording(): CallRecording
    {
        return $this->recording;
    }

    public function getSpeaker(): string
    {
        return $this->speaker;
    }

    public function getDiscordUserId(): ?string
    {
        return $this->discordUserId;
    }

    public function setDiscordUserId(?string $discordUserId): self
    {
        $this->discordUserId = $discordUserId;
        return $this;
    }

    public function getParticipant(): ?LarpParticipant
    {
        return $this->participant;
    }

    public function setParticipant(?LarpParticipant $participant): self
    {
        $this->participant = $participant;
        return $this;
    }

    public function getAudioFilename(): ?string
    {
        return $this->audioFilename;
    }

    public function getTranscriptFilename(): ?string
    {
        return $this->transcriptFilename;
    }

    public function hasTranscript(): bool
    {
        return $this->transcriptFilename !== null;
    }

    public function getStatus(): CallTrackStatus
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === CallTrackStatus::PENDING;
    }

    public function isTranscribed(): bool
    {
        return $this->status === CallTrackStatus::TRANSCRIBED;
    }

    /**
     * @return list<TranscriptSegment>
     */
    public function getSegments(): array
    {
        return array_map(TranscriptSegment::fromArray(...), $this->segments);
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function registerAttempt(): void
    {
        ++$this->attempts;
    }

    /**
     * @param list<TranscriptSegment> $segments
     */
    public function markTranscribed(array $segments): void
    {
        $this->segments = array_map(static fn (TranscriptSegment $segment): array => $segment->toArray(), $segments);
        $this->status = CallTrackStatus::TRANSCRIBED;
        $this->forgetFiles();
    }

    public function markFailed(string $failureReason): void
    {
        $this->status = CallTrackStatus::FAILED;
        $this->failureReason = $failureReason;
        $this->forgetFiles();
    }

    private function forgetFiles(): void
    {
        $this->audioFilename = null;
        $this->transcriptFilename = null;
    }
}
