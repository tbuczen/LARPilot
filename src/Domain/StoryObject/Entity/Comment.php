<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: 'App\Domain\StoryObject\Repository\CommentRepository')]
#[ORM\Table(name: 'comment')]
#[ORM\Index(name: 'idx_comment_story_object', columns: ['story_object_id'])]
#[ORM\Index(name: 'idx_comment_parent', columns: ['parent_id'])]
class Comment
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: StoryObject::class)]
    #[ORM\JoinColumn(name: 'story_object_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StoryObject $storyObject;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false)]
    private User $author;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    /**
     * Parent comment for threaded discussions (nullable for top-level comments)
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Comment $parent = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isResolved = false;


    public function getStoryObject(): StoryObject
    {
        return $this->storyObject;
    }

    public function setStoryObject(StoryObject $storyObject): self
    {
        $this->storyObject = $storyObject;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    public function setParent(?Comment $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    public function setIsResolved(bool $isResolved): self
    {
        $this->isResolved = $isResolved;

        return $this;
    }

    /**
     * Check if this comment is a top-level comment (no parent)
     */
    public function isTopLevel(): bool
    {
        return $this->parent === null;
    }
}
