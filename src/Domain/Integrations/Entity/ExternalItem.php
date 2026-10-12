<?php

namespace App\Domain\Integrations\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Entity\Enum\ExternalItemAvailability;
use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ExternalItemReviewStatus;
use App\Domain\Integrations\Repository\ExternalItemRepository;
use App\Domain\Integrations\Service\ExcerptNormalizer;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExternalItemRepository::class)]
#[ORM\Table(name: 'external_item')]
#[ORM\UniqueConstraint(name: 'uniq_external_item_source_external_id', columns: ['source_id', 'external_id'])]
#[ORM\Index(columns: ['source_id', 'review_status'])]
class ExternalItem implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: ExternalSource::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ExternalSource $source;

    #[ORM\Column(type: 'string')]
    private string $externalId;

    #[ORM\Column(type: 'string', length: 30, enumType: ExternalItemKind::class)]
    private ExternalItemKind $kind;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $parentExternalId = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $externalUpdatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ExternalItemAvailability::class, options: ['default' => 'ok'])]
    private ExternalItemAvailability $availability = ExternalItemAvailability::Ok;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $raw = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ExternalItemReviewStatus::class, options: ['default' => 'unreviewed'])]
    private ExternalItemReviewStatus $reviewStatus = ExternalItemReviewStatus::Unreviewed;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getSource(): ExternalSource
    {
        return $this->source;
    }

    public function setSource(ExternalSource $source): self
    {
        $this->source = $source;
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

    public function getKind(): ExternalItemKind
    {
        return $this->kind;
    }

    public function setKind(ExternalItemKind $kind): self
    {
        $this->kind = $kind;
        return $this;
    }

    public function getParentExternalId(): ?string
    {
        return $this->parentExternalId;
    }

    public function setParentExternalId(?string $parentExternalId): self
    {
        $this->parentExternalId = $parentExternalId;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title === null ? null : mb_substr($title, 0, 500);
        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = ExcerptNormalizer::normalize($excerpt);
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;
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

    public function getExternalUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->externalUpdatedAt;
    }

    public function setExternalUpdatedAt(?\DateTimeImmutable $externalUpdatedAt): self
    {
        $this->externalUpdatedAt = $externalUpdatedAt;
        return $this;
    }

    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): self
    {
        $this->lastSeenAt = $lastSeenAt;
        return $this;
    }

    public function getAvailability(): ExternalItemAvailability
    {
        return $this->availability;
    }

    public function setAvailability(ExternalItemAvailability $availability): self
    {
        $this->availability = $availability;
        return $this;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function setRaw(?array $raw): self
    {
        $this->raw = $raw;
        return $this;
    }

    public function getReviewStatus(): ExternalItemReviewStatus
    {
        return $this->reviewStatus;
    }

    public function setReviewStatus(ExternalItemReviewStatus $reviewStatus): self
    {
        $this->reviewStatus = $reviewStatus;
        return $this;
    }
}
