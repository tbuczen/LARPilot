# Knowledge Document System

## Overview

The Knowledge Document System provides a flexible document management solution for LARPilot with granular visibility controls. Documents can be owned by and visible to any StoryObject (Characters, Factions, Quests, Threads, etc.), leveraging the existing StoryObject inheritance hierarchy.

**Key Features:**
- **Universal Ownership**: Any StoryObject can own documents
- **Flexible Visibility**: Documents can be public or restricted to specific StoryObjects
- **Multi-Owner Support**: Documents can have multiple owners
- **Extensible**: Automatically supports all current and future StoryObject types

## Architecture

### Entity Structure

**Entity**: `App\Domain\StoryObject\Entity\KnowledgeDocument`

The system uses **many-to-many relationships with StoryObject**:

```php
class KnowledgeDocument {
    // Content
    private string $title;
    private ?string $content;
    private ?string $category;
    private bool $isPublic = false;

    // Context
    private Larp $larp;  // All documents belong to a LARP

    // Ownership (many-to-many with StoryObject)
    private Collection $owners;  // Characters, Factions, Quests, etc.

    // Visibility (many-to-many with StoryObject)
    private Collection $visibleTo;  // Characters, Factions, Quests, etc.
}
```

**Design Benefits:**
- Single collection handles all owner types (Character, Faction, Quest, Thread, Event, Place, Item)
- Leverages Doctrine's JOINED inheritance strategy
- No discriminator column needed
- Automatically supports new StoryObject types
- Clean OOP design following open/closed principle

### Database Schema

**Table**: `knowledge_document`

```sql
CREATE TABLE knowledge_document (
    id UUID PRIMARY KEY,
    larp_id UUID NOT NULL,
    creator_id UUID,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    is_public BOOLEAN DEFAULT false NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);
```

**Join Tables**:

```sql
-- Ownership (many-to-many with any StoryObject)
CREATE TABLE knowledge_document_owner (
    knowledge_document_id UUID NOT NULL,
    story_object_id UUID NOT NULL,
    PRIMARY KEY(knowledge_document_id, story_object_id),
    FOREIGN KEY (story_object_id) REFERENCES story_object(id) ON DELETE CASCADE
);

-- Visibility (many-to-many with any StoryObject)
CREATE TABLE knowledge_document_visible_to (
    knowledge_document_id UUID NOT NULL,
    story_object_id UUID NOT NULL,
    PRIMARY KEY(knowledge_document_id, story_object_id),
    FOREIGN KEY (story_object_id) REFERENCES story_object(id) ON DELETE CASCADE
);
```

## Visibility Logic

### Visibility Rules

A character can see a document if ANY of these conditions are met:

1. **Public Flag**: Document is marked as public
2. **Direct Owner**: Character owns the document
3. **Faction Owner**: Character is a member of a faction that owns the document
4. **Direct Visibility**: Character is in the `visibleTo` collection
5. **Faction Visibility**: Character is a member of a faction in the `visibleTo` collection

### Implementation

```php
public function isVisibleToCharacter(Character $character): bool
{
    // 1. Public documents
    if ($this->isPublic) {
        return true;
    }

    // 2. Check all owners
    foreach ($this->owners as $owner) {
        // Direct character ownership
        if ($owner instanceof Character && $owner->getId()->equals($character->getId())) {
            return true;
        }

        // Faction ownership - member access
        if ($owner instanceof Faction && $owner->getMembers()->contains($character)) {
            return true;
        }
    }

    // 3. Check all visibility grants
    foreach ($this->visibleTo as $storyObject) {
        // Direct character visibility
        if ($storyObject instanceof Character && $storyObject->getId()->equals($character->getId())) {
            return true;
        }

        // Faction visibility - member access
        if ($storyObject instanceof Faction && $storyObject->getMembers()->contains($character)) {
            return true;
        }
    }

    return false;
}
```

**Extensibility**: This logic can easily be extended to support Quest participants, Thread members, Event attendees, etc.

## Entity Methods

### Ownership Management

```php
// Add/remove owners (any StoryObject type)
$document->addOwner(StoryObject $owner): self
$document->removeOwner(StoryObject $owner): self
$document->getOwners(): Collection<StoryObject>
$document->isOwnedBy(StoryObject $storyObject): bool
```

### Visibility Management

```php
// Add/remove visibility grants (any StoryObject type)
$document->addVisibleTo(StoryObject $storyObject): self
$document->removeVisibleTo(StoryObject $storyObject): self
$document->getVisibleTo(): Collection<StoryObject>
```

### Visibility Checks

```php
// Check if character can see document
$document->isVisibleToCharacter(Character $character): bool
```

## Repository Methods

**Repository**: `App\Domain\StoryObject\Repository\KnowledgeDocumentRepository`

### Finding by Owner

```php
// Find all documents owned by any StoryObject
$repo->findByOwner(StoryObject $owner): array

// Examples:
$characterDocs = $repo->findByOwner($character);
$factionDocs = $repo->findByOwner($faction);
$questDocs = $repo->findByOwner($quest);
$threadDocs = $repo->findByOwner($thread);
```

### Finding LARP-Wide Documents

```php
// Find documents without specific owners (LARP-wide knowledge base)
$repo->findLarpDocuments(Larp $larp): array
```

### Finding Visible Documents

```php
// Find all documents visible to a character
$repo->findVisibleToCharacter(Character $character): array

// Get query builder for custom filtering
$qb = $repo->createVisibleToCharacterQueryBuilder(Character $character): QueryBuilder
```

## Form Usage

**Form Type**: `App\Domain\StoryObject\Form\Type\KnowledgeDocumentType`

### Form Fields

```php
use App\Domain\StoryObject\Form\Type\KnowledgeDocumentType;

$form = $this->createForm(KnowledgeDocumentType::class, $document, [
    'larp' => $larp,  // Required for scoped queries
]);
```

**Fields:**
- `title` - Document title
- `category` - Optional category (lore, history, rules, faction, world, other)
- `content` - WYSIWYG editor for document content
- `owners` - Multiple StoryObjects (any type)
- `isPublic` - Public visibility checkbox
- `visibleTo` - Multiple StoryObjects for additional visibility (any type)

### Example Usage

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);

$form = $this->createForm(KnowledgeDocumentType::class, $document, [
    'larp' => $larp,
]);

$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $em->persist($document);
    $em->flush();
}
```

## Testing with Foundry Factory

**Factory**: `Tests\Support\Factory\StoryObject\KnowledgeDocumentFactory`

### Basic Usage

```php
use Tests\Support\Factory\StoryObject\KnowledgeDocumentFactory;

// LARP-wide document (no owner)
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->create();

// Public document
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->public()
    ->withTitle('World Lore')
    ->withCategory('lore')
    ->create();
```

### Ownership

```php
// Character-owned document
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->ownedBy($character)
    ->withTitle('Character Backstory')
    ->create();

// Faction-owned document
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->ownedBy($faction)
    ->withTitle('Faction History')
    ->create();

// Quest-owned document
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->ownedBy($quest)
    ->withTitle('Quest Briefing')
    ->create();

// Multi-owner document
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->ownedBy($character1)
    ->ownedBy($character2)
    ->ownedBy($faction)
    ->create();
```

### Visibility Control

```php
// Document visible to specific character
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->private()
    ->visibleTo($character)
    ->create();

// Document visible to faction members
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->private()
    ->visibleTo($faction)
    ->create();

// Combined visibility
$document = KnowledgeDocumentFactory::new()
    ->forLarp($larp)
    ->ownedBy($faction1)
    ->visibleTo($character)  // Spy has access
    ->visibleTo($faction2)   // Allied faction has access
    ->create();
```

### Available Factory Methods

**Context:**
- `forLarp(Larp $larp)` - Set LARP context

**Ownership:**
- `ownedBy(StoryObject $owner)` - Add owner (Character, Faction, Quest, Thread, etc.)

**Visibility:**
- `public()` - Public to all characters
- `private()` - Private (default)
- `visibleTo(StoryObject $storyObject)` - Grant visibility (Character, Faction, Quest, etc.)

**Content:**
- `withTitle(string $title)` - Set title
- `withContent(string $content)` - Set content
- `withCategory(string $category)` - Set category
- `withCreator(User $user)` - Set creator

**Category Shortcuts:**
- `lore()`, `history()`, `rules()`, `faction()`, `world()` - Category presets

## Use Cases

### 1. LARP-Wide Knowledge Base

Public lore document visible to all players:

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);
$document->setTitle('The Great War of 1845');
$document->setCategory('history');
$document->setContent('<p>Long ago, in a distant land...</p>');
$document->setIsPublic(true);
// No owners = LARP-wide document
```

### 2. Character Backstory

Private character document visible only to them:

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);
$document->addOwner($character);
$document->setTitle('My Secret Past');
$document->setContent('<p>I was born in...</p>');
$document->setIsPublic(false);
```

### 3. Faction Strategy

Faction document visible to all members:

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);
$document->addOwner($faction);
$document->setTitle('Infiltration Plan');
$document->setCategory('faction');
$document->setContent('<p>Phase 1: Gather intel...</p>');
```

### 4. Shared Secret

Document visible to multiple entities:

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);
$document->setTitle('The Hidden Vault');
$document->setCategory('lore');
$document->setContent('<p>The location...</p>');
$document->setIsPublic(false);

// Shared between two factions and a spy
$document->addVisibleTo($faction1);
$document->addVisibleTo($faction2);
$document->addVisibleTo($spyCharacter);
```

### 5. Quest Documentation

Quest briefing visible to quest participants:

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);
$document->addOwner($quest);
$document->setTitle('Quest Objectives');
$document->setContent('<p>Your mission is to...</p>');

// All characters assigned to the quest can access it
// (Future enhancement: automatic visibility to quest participants)
```

### 6. Thread Clues

Clues tied to a story thread:

```php
$document = new KnowledgeDocument();
$document->setLarp($larp);
$document->setTitle('Investigation Findings');
$document->addVisibleTo($thread);

// All characters in the thread can access the clues
// (Future enhancement: automatic visibility to thread participants)
```

## Controller Routes

**Controller**: `App\Domain\StoryObject\Controller\Backoffice\KnowledgeDocumentController`

**Base Route**: `/backoffice/larp/{larp}/knowledge/`

### Available Routes

- `GET /` - List all documents with filtering
  - **Route Name**: `backoffice_larp_knowledge_list`
  - **Template**: `domain/story_object/knowledge_document/list.html.twig`

- `GET|POST /create` - Create new document
  - **Route Name**: `backoffice_larp_knowledge_create`
  - **Template**: `domain/story_object/knowledge_document/modify.html.twig`

- `GET|POST /{knowledge}/edit` - Edit existing document
  - **Route Name**: `backoffice_larp_knowledge_edit`
  - **Template**: `domain/story_object/knowledge_document/modify.html.twig`

- `POST /{knowledge}/delete` - Delete document
  - **Route Name**: `backoffice_larp_knowledge_delete`
  - **Redirect**: Back to list

## Translation Keys

All translations are in `translations/forms.en.yaml` under `knowledge_document`:

**Form Fields:**
- `knowledge_document.title` - Document title field
- `knowledge_document.content` - Content editor
- `knowledge_document.owners` - Owners selector
- `knowledge_document.owners_help` - Ownership explanation
- `knowledge_document.is_public` - Public visibility checkbox
- `knowledge_document.is_public_help` - Public visibility explanation
- `knowledge_document.visible_to` - Additional visibility selector
- `knowledge_document.visible_to_help` - Additional visibility explanation
- `knowledge_document.category` - Category selector

**Categories:**
- `knowledge_document.category.lore` - "Lore & World-Building"
- `knowledge_document.category.history` - "History & Background"
- `knowledge_document.category.rules` - "Rules & Mechanics"
- `knowledge_document.category.faction` - "Faction Information"
- `knowledge_document.category.world` - "World Information"
- `knowledge_document.category.other` - "Other"

## Testing

### Test Files

- `tests/Functional/StoryObject/KnowledgeDocument/KnowledgeDocumentCrudCest.php` - CRUD and permissions
- `tests/Functional/StoryObject/KnowledgeDocument/KnowledgeDocumentVisibilityCest.php` - Visibility logic

### Running Tests

```bash
# Run all knowledge document tests
make test-filter FILTER=KnowledgeDocument

# Run specific test file
make test-filter FILTER=KnowledgeDocumentVisibilityCest

# Run all functional tests
make test-functional
```

## Performance Considerations

### Query Optimization

The system uses efficient LEFT JOINs with indexed relationships:

```php
$qb->leftJoin('kd.owners', 'owners')
    ->leftJoin('kd.visibleTo', 'visibleTo')
    ->where('kd.larp = :larp')
    ->andWhere($qb->expr()->orX(
        'kd.isPublic = true',
        'owners.id = :characterId',
        'visibleTo.id = :characterId',
        // Faction membership checks via subqueries
    ))
    ->setParameter('larp', $larp)
    ->setParameter('characterId', $character->getId())
    ->distinct();
```

### Indexes

- `knowledge_document (larp_id, title)`
- `knowledge_document_owner (knowledge_document_id, story_object_id)`
- `knowledge_document_visible_to (knowledge_document_id, story_object_id)`
- `story_object (id)` - Doctrine's JOINED inheritance table

## Future Enhancements

The StoryObject-based design enables advanced features:

### 1. Quest Participant Access

```php
// Future: Automatic visibility to quest participants
foreach ($document->visibleTo as $storyObject) {
    if ($storyObject instanceof Quest) {
        foreach ($storyObject->getCharacters() as $participant) {
            // Grant access
        }
    }
}
```

### 2. Thread Member Access

```php
// Future: Thread documents visible to all thread participants
foreach ($document->owners as $owner) {
    if ($owner instanceof Thread) {
        foreach ($owner->getCharacters() as $char) {
            // Grant access
        }
    }
}
```

### 3. Relationship-Based Visibility

```php
// Future: Access based on character relationships
foreach ($document->owners as $owner) {
    if ($owner instanceof Character) {
        foreach ($owner->getRelationsFrom() as $relation) {
            if ($relation->getType() === 'ally') {
                // Grant access to allies
            }
        }
    }
}
```

### 4. Comments System

```php
// Future: Comments on documents
class DocumentComment {
    private KnowledgeDocument $document;
    private Character $author;
    private string $content;
    private DateTime $createdAt;
}
```

### 5. Version History

Already partially supported via Gedmo Loggable extension.

## Summary

The Knowledge Document System provides a **flexible, extensible document management solution** by:

✅ **Leveraging StoryObject inheritance** - Works with all current and future types
✅ **Simple OOP design** - Clean, maintainable code
✅ **Flexible relationships** - Multiple owners, complex visibility rules
✅ **Extensible architecture** - Easy to add new features
✅ **Performance optimized** - Efficient queries with proper indexing
✅ **Well tested** - Comprehensive functional test coverage
✅ **Type safe** - PHP 8.2+ with strict types

This architecture demonstrates the value of **thinking about extensibility** and **leveraging existing patterns** when designing entity relationships.

## Related Documentation

- **[Refactor Documentation](KNOWLEDGE_DOCUMENT_REFACTOR.md)** - Details on the refactor from enum-based to StoryObject-based design
- **[Domain Architecture](DOMAIN_ARCHITECTURE.md)** - Overall DDD structure
- **[StoryObject System](../src/Domain/StoryObject/)** - StoryObject inheritance hierarchy
