<?php

namespace App\Domain\Integrations\Entity;

use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Integrations\Service\Suggestion\LinkSuggestionCandidate;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'external_item_suggestion')]
#[ORM\Index(columns: ['item_id', 'score'])]
class ExternalItemSuggestion
{
    use UuidTraitEntity;

    #[ORM\ManyToOne(targetEntity: ExternalItem::class, inversedBy: 'suggestions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ExternalItem $item;

    #[ORM\Column(type: 'string', length: 30)]
    private string $source;

    #[ORM\Column(type: 'float')]
    private float $score;

    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?StoryObject $storyObject = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true, enumType: TargetType::class)]
    private ?TargetType $targetType = null;

    #[ORM\ManyToOne(targetEntity: Tag::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Tag $tag = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(ExternalItem $item, string $source, float $score)
    {
        $this->id = Uuid::v4();
        $this->item = $item;
        $this->source = $source;
        $this->score = $score;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getItem(): ExternalItem
    {
        return $this->item;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getStoryObject(): ?StoryObject
    {
        return $this->storyObject;
    }

    public function setStoryObject(?StoryObject $storyObject): self
    {
        $this->storyObject = $storyObject;
        return $this;
    }

    public function getTargetType(): ?TargetType
    {
        return $this->targetType;
    }

    public function setTargetType(?TargetType $targetType): self
    {
        $this->targetType = $targetType;
        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isLinkSuggestion(): bool
    {
        return $this->storyObject instanceof StoryObject;
    }

    public function signature(): string
    {
        return sprintf('%s|%s|%.4F', $this->source, LinkSuggestionCandidate::keyFor($this->storyObject, $this->targetType, $this->tag), $this->score);
    }
}
