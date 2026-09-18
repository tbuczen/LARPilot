<?php

declare(strict_types=1);

namespace Tests\Support\Factory\StoryObject;

use App\Domain\StoryObject\Entity\KnowledgeDocument;
use App\Domain\StoryObject\Entity\StoryObject;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<KnowledgeDocument>
 */
final class KnowledgeDocumentFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return KnowledgeDocument::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(3),
            'content' => self::faker()->optional()->paragraphs(3, true),
            'larp' => LarpFactory::new(),
            'isPublic' => false,
            'category' => self::faker()->optional()->randomElement(['lore', 'history', 'rules', 'faction', 'world', 'other']),
            'createdBy' => UserFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    // ========================================================================
    // Factory States
    // ========================================================================

    /**
     * Public document visible to all characters
     */
    public function public(): self
    {
        return $this->with([
            'isPublic' => true,
        ]);
    }

    /**
     * Private document (not public, visibility controlled by relations)
     */
    public function private(): self
    {
        return $this->with([
            'isPublic' => false,
        ]);
    }

    // ========================================================================
    // Factory Configuration Methods
    // ========================================================================

    /**
     * Document for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    // ========================================================================
    // Ownership Methods
    // ========================================================================

    /**
     * Add an owner to this document
     * Can be any StoryObject (Character, Faction, Quest, Thread, etc.)
     */
    public function ownedBy(StoryObject|null $owner): self
    {
        return $this->afterInstantiate(function (KnowledgeDocument $document) use ($owner) {
            $document->addOwner($owner);
        });
    }

    // ========================================================================
    // Content Configuration
    // ========================================================================

    /**
     * Document with specific title
     */
    public function withTitle(string $title): self
    {
        return $this->with([
            'title' => $title,
        ]);
    }

    /**
     * Document with specific category
     */
    public function withCategory(string $category): self
    {
        return $this->with([
            'category' => $category,
        ]);
    }

    /**
     * Document with specific content
     */
    public function withContent(string $content): self
    {
        return $this->with([
            'content' => $content,
        ]);
    }

    /**
     * Grant visibility to a StoryObject
     * Can be any StoryObject (Character, Faction, Quest, Thread, etc.)
     * For Factions, all members will be able to see the document
     */
    public function visibleTo(StoryObject|null $storyObject): self
    {
        return $this->afterInstantiate(function (KnowledgeDocument $document) use ($storyObject) {
            $document->addVisibleTo($storyObject);
        });
    }

    /**
     * Document with creator
     */
    public function withCreator(mixed $user): self
    {
        return $this->with([
            'createdBy' => $user,
        ]);
    }

    // ========================================================================
    // Category Shortcuts
    // ========================================================================

    public function lore(): self
    {
        return $this->withCategory('lore');
    }

    public function history(): self
    {
        return $this->withCategory('history');
    }

    public function rules(): self
    {
        return $this->withCategory('rules');
    }

    public function faction(): self
    {
        return $this->withCategory('faction');
    }

    public function world(): self
    {
        return $this->withCategory('world');
    }
}
