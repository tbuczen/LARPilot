<?php

namespace App\Domain\Integrations\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceSyncStatus;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExternalSourceRepository::class)]
#[ORM\Table(name: 'external_source')]
#[ORM\UniqueConstraint(name: 'uniq_external_source_integration_external_id', columns: ['integration_id', 'external_id'])]
#[ORM\Index(columns: ['next_sync_at'])]
class ExternalSource implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: LarpIntegration::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LarpIntegration $integration;

    #[ORM\Column(type: 'string', length: 50, enumType: ExternalSourceKind::class)]
    private ExternalSourceKind $kind;

    #[ORM\Column(type: 'string')]
    private string $externalId;

    #[ORM\Column(type: 'string')]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $syncCursor = null;

    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $config = [];

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: ExternalSourceSyncStatus::class)]
    private ?ExternalSourceSyncStatus $lastSyncStatus = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastSyncError = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $nextSyncAt = null;

    /** @var Collection<int, ExternalItem> */
    #[ORM\OneToMany(targetEntity: ExternalItem::class, mappedBy: 'source', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->items = new ArrayCollection();
    }

    public function getIntegration(): LarpIntegration
    {
        return $this->integration;
    }

    public function setIntegration(LarpIntegration $integration): self
    {
        $this->integration = $integration;
        return $this;
    }

    public function getKind(): ExternalSourceKind
    {
        return $this->kind;
    }

    public function setKind(ExternalSourceKind $kind): self
    {
        $this->kind = $kind;
        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getSyncCursor(): ?array
    {
        return $this->syncCursor;
    }

    public function setSyncCursor(?array $syncCursor): self
    {
        $this->syncCursor = $syncCursor;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;
        return $this;
    }

    public function getLastSyncStatus(): ?ExternalSourceSyncStatus
    {
        return $this->lastSyncStatus;
    }

    public function setLastSyncStatus(?ExternalSourceSyncStatus $lastSyncStatus): self
    {
        $this->lastSyncStatus = $lastSyncStatus;
        return $this;
    }

    public function getLastSyncError(): ?string
    {
        return $this->lastSyncError;
    }

    public function setLastSyncError(?string $lastSyncError): self
    {
        $this->lastSyncError = $lastSyncError;
        return $this;
    }

    public function getNextSyncAt(): ?\DateTimeImmutable
    {
        return $this->nextSyncAt;
    }

    public function setNextSyncAt(?\DateTimeImmutable $nextSyncAt): self
    {
        $this->nextSyncAt = $nextSyncAt;
        return $this;
    }

    /** @return Collection<int, ExternalItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ExternalItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSource($this);
        }
        return $this;
    }

    public function removeItem(ExternalItem $item): self
    {
        $this->items->removeElement($item);
        return $this;
    }
}
