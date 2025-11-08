<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Entity;

use App\Domain\Account\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: 'App\Domain\StoryObject\Repository\CommentRepository')]
#[ORM\Table(name: 'comment')]
#[ORM\Index(name: 'idx_comment_story_object', columns: ['story_object_id'])]
#[ORM\Index(name: 'idx_comment_parent', columns: ['parent_id'])]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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
