<?php

namespace App\Domain\CallTranscript\Entity;

use App\Domain\CallTranscript\DTO\CallSummary;
use App\Domain\CallTranscript\DTO\TranscriptSegment;
use App\Domain\CallTranscript\Entity\Enum\CallOutput;
use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Entity\Enum\CallRecordingStatus;
use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\LarpAwareInterface;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CallRecordingRepository::class)]
#[ORM\Table(name: 'call_recording')]
#[ORM\Index(columns: ['larp_id'])]
class CallRecording implements CreatorAwareInterface, Timestampable, LarpAwareInterface
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Larp $larp = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 20, enumType: CallRecordingStatus::class)]
    private CallRecordingStatus $status = CallRecordingStatus::AWAITING_REVIEW;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $language;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $recordingStartedAt = null;

    /**
     * @var list<array{start: float, text: string}>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $notes = [];

    #[ORM\ManyToOne(targetEntity: KnowledgeDocument::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?KnowledgeDocument $knowledgeDocument = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(length: 20, enumType: CallOutputStatus::class, options: ['default' => 'skipped'])]
    private CallOutputStatus $summaryStatus = CallOutputStatus::SKIPPED;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summaryFailureReason = null;

    #[ORM\Column(length: 20, enumType: CallOutputStatus::class, options: ['default' => 'skipped'])]
    private CallOutputStatus $googleDocStatus = CallOutputStatus::SKIPPED;

    #[ORM\ManyToOne(targetEntity: SharedFile::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SharedFile $googleDocFolder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleDocId = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $googleDocUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $googleDocFailureReason = null;

    #[ORM\Column(length: 20, enumType: CallOutputStatus::class, options: ['default' => 'skipped'])]
    private CallOutputStatus $discordStatus = CallOutputStatus::SKIPPED;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $discordChannelId = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $discordThreadId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $discordThreadUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $discordFailureReason = null;

    /**
     * @var Collection<int, CallRecordingTrack>
     */
    #[ORM\OneToMany(targetEntity: CallRecordingTrack::class, mappedBy: 'recording', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['speaker' => 'ASC'])]
    private Collection $tracks;

    public function __construct(Larp $larp, string $title, ?string $language)
    {
        $this->id = Uuid::v4();
        $this->larp = $larp;
        $this->title = $title;
        $this->language = $language;
        $this->tracks = new ArrayCollection();
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): CallRecordingStatus
    {
        return $this->status;
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === CallRecordingStatus::AWAITING_REVIEW;
    }

    public function isTranscribing(): bool
    {
        return $this->status === CallRecordingStatus::TRANSCRIBING;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getRecordingStartedAt(): ?DateTimeImmutable
    {
        return $this->recordingStartedAt;
    }

    public function setRecordingStartedAt(?DateTimeImmutable $recordingStartedAt): self
    {
        $this->recordingStartedAt = $recordingStartedAt;
        return $this;
    }

    /**
     * @return list<TranscriptSegment>
     */
    public function getNotes(): array
    {
        return array_map(TranscriptSegment::fromArray(...), $this->notes);
    }

    /**
     * @param list<TranscriptSegment> $notes
     */
    public function setNotes(array $notes): self
    {
        $this->notes = array_map(static fn (TranscriptSegment $note): array => $note->toArray(), $notes);
        return $this;
    }

    public function getKnowledgeDocument(): ?KnowledgeDocument
    {
        return $this->knowledgeDocument;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    /**
     * @return Collection<int, CallRecordingTrack>
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function addTrack(CallRecordingTrack $track): self
    {
        if (!$this->tracks->contains($track)) {
            $this->tracks->add($track);
        }
        return $this;
    }

    public function removeTrack(CallRecordingTrack $track): self
    {
        $this->tracks->removeElement($track);
        return $this;
    }

    public function startTranscription(): void
    {
        $this->status = CallRecordingStatus::TRANSCRIBING;
    }

    public function complete(KnowledgeDocument $knowledgeDocument, ?string $failureReason): void
    {
        $this->status = CallRecordingStatus::COMPLETED;
        $this->knowledgeDocument = $knowledgeDocument;
        $this->failureReason = $failureReason;
    }

    public function fail(string $failureReason): void
    {
        $this->status = CallRecordingStatus::FAILED;
        $this->failureReason = $failureReason;
        foreach (CallOutput::cases() as $output) {
            if ($this->outputStatus($output) === CallOutputStatus::PENDING) {
                $this->skipOutput($output);
            }
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === CallRecordingStatus::COMPLETED;
    }

    public function outputStatus(CallOutput $output): CallOutputStatus
    {
        return match ($output) {
            CallOutput::SUMMARY => $this->summaryStatus,
            CallOutput::GOOGLE_DOC => $this->googleDocStatus,
            CallOutput::DISCORD => $this->discordStatus,
        };
    }

    public function outputFailureReason(CallOutput $output): ?string
    {
        return match ($output) {
            CallOutput::SUMMARY => $this->summaryFailureReason,
            CallOutput::GOOGLE_DOC => $this->googleDocFailureReason,
            CallOutput::DISCORD => $this->discordFailureReason,
        };
    }

    /**
     * @return list<CallOutput>
     */
    public function requestedOutputs(): array
    {
        $requestedOutputs = [];
        foreach (CallOutput::cases() as $output) {
            if ($this->outputStatus($output) !== CallOutputStatus::SKIPPED) {
                $requestedOutputs[] = $output;
            }
        }

        return $requestedOutputs;
    }

    public function getSummaryStatus(): CallOutputStatus
    {
        return $this->summaryStatus;
    }

    public function getSummary(): ?CallSummary
    {
        return $this->summary === null ? null : CallSummary::fromArray($this->summary);
    }

    public function getSummaryFailureReason(): ?string
    {
        return $this->summaryFailureReason;
    }

    public function requestSummary(bool $requested): void
    {
        $this->summaryStatus = $requested ? CallOutputStatus::PENDING : CallOutputStatus::SKIPPED;
        $this->summaryFailureReason = null;
    }

    public function completeSummary(CallSummary $summary): void
    {
        $this->summary = $summary->toArray();
        $this->summaryStatus = CallOutputStatus::DONE;
        $this->summaryFailureReason = null;
    }

    public function failSummary(string $failureReason): void
    {
        $this->summaryStatus = CallOutputStatus::FAILED;
        $this->summaryFailureReason = $failureReason;
    }

    public function getGoogleDocStatus(): CallOutputStatus
    {
        return $this->googleDocStatus;
    }

    public function getGoogleDocFolder(): ?SharedFile
    {
        return $this->googleDocFolder;
    }

    public function getGoogleDocId(): ?string
    {
        return $this->googleDocId;
    }

    public function getGoogleDocUrl(): ?string
    {
        return $this->googleDocUrl;
    }

    public function requestGoogleDoc(?SharedFile $folder): void
    {
        $this->googleDocFolder = $folder;
        $this->googleDocStatus = $folder === null ? CallOutputStatus::SKIPPED : CallOutputStatus::PENDING;
        $this->googleDocFailureReason = null;
    }

    public function retryGoogleDoc(): void
    {
        $this->googleDocStatus = CallOutputStatus::PENDING;
        $this->googleDocFailureReason = null;
    }

    public function completeGoogleDoc(string $documentId, string $documentUrl): void
    {
        $this->googleDocId = $documentId;
        $this->googleDocUrl = $documentUrl;
        $this->googleDocStatus = CallOutputStatus::DONE;
        $this->googleDocFailureReason = null;
    }

    public function failGoogleDoc(string $failureReason): void
    {
        $this->googleDocStatus = CallOutputStatus::FAILED;
        $this->googleDocFailureReason = $failureReason;
    }

    public function getDiscordStatus(): CallOutputStatus
    {
        return $this->discordStatus;
    }

    public function getDiscordChannelId(): ?string
    {
        return $this->discordChannelId;
    }

    public function getDiscordThreadId(): ?string
    {
        return $this->discordThreadId;
    }

    public function getDiscordThreadUrl(): ?string
    {
        return $this->discordThreadUrl;
    }

    public function requestDiscordPost(?string $channelId): void
    {
        $this->discordChannelId = $channelId;
        $this->discordStatus = $channelId === null ? CallOutputStatus::SKIPPED : CallOutputStatus::PENDING;
        $this->discordFailureReason = null;
    }

    public function retryDiscordPost(): void
    {
        $this->discordStatus = CallOutputStatus::PENDING;
        $this->discordFailureReason = null;
    }

    public function startDiscordThread(string $threadId, string $threadUrl): void
    {
        $this->discordThreadId = $threadId;
        $this->discordThreadUrl = $threadUrl;
    }

    public function completeDiscordPost(): void
    {
        $this->discordStatus = CallOutputStatus::DONE;
        $this->discordFailureReason = null;
    }

    public function failDiscordPost(string $failureReason): void
    {
        $this->discordStatus = CallOutputStatus::FAILED;
        $this->discordFailureReason = $failureReason;
    }

    private function skipOutput(CallOutput $output): void
    {
        match ($output) {
            CallOutput::SUMMARY => $this->summaryStatus = CallOutputStatus::SKIPPED,
            CallOutput::GOOGLE_DOC => $this->googleDocStatus = CallOutputStatus::SKIPPED,
            CallOutput::DISCORD => $this->discordStatus = CallOutputStatus::SKIPPED,
        };
    }
}
