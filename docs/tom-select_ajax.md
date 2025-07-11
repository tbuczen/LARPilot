# Tom-Select with AJAX Entity Creation
This documentation explains how to use Tom-Select with automatic entity creation via AJAX calls for entities that implement the . `TargetableInterface`
## Overview
The system allows users to create new entities on-the-fly while typing in autocomplete fields. When a user types a name that doesn't exist in the database, they can create it directly from the form field without navigating away.
## Components
### 1. TargetableInterface
Entities that support AJAX creation must implement the : `TargetableInterface`
``` php
<?php

namespace App\Entity;

use App\Entity\Enum\TargetType;
use Symfony\Component\Uid\Uuid;

interface TargetableInterface
{
    public function getId(): Uuid;
    public static function getTargetType(): TargetType;
}
```
### 2. Entity Implementation
Your entity must implement the interface and provide the required methods:
``` php
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag implements CreatorAwareInterface, Timestampable, TargetableInterface
{
    // ... other properties and methods

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Tag;
    }
}
```
**Important**: The entity must have and `setTitle()` methods, as these are used by the autocomplete controller. `getTitle()`
### 3. Form Configuration
In your form type, configure the EntityType field with the appropriate Tom-Select options:
``` php
->add('tags', EntityType::class, [
    'class' => Tag::class,
    'choice_label' => 'title', // or 'name' depending on your entity
    'label' => 'form.thread.tags',
    'required' => false,
    'multiple' => true,
    'autocomplete' => true,
    'query_builder' => function (TagRepository $repo) use ($larp) {
        return $repo->createQueryBuilder('t')
            ->where('t.larp = :larp')
            ->setParameter('larp', $larp);
    },
    'tom_select_options' => [
        'create' => true,        // Enable creation of new items
        'persist' => false,      // Don't persist immediately (handled by AJAX)
    ],
])
```
### 4. Form Options
Your form must pass the `larp` option to enable the extension to work:
``` php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Thread::class,
        'translation_domain' => 'forms',
        'larp' => null,
    ]);

    $resolver->setRequired('larp');
    $resolver->setAllowedTypes('larp', Larp::class);
}
```
## How It Works
1. **Form Extension**: The automatically detects EntityType fields with `tom_select_options` containing `create: true` and `persist: false`. `FindOrCreateEntityExtension`
2. **Stimulus Controller**: The extension adds the `custom-autocomplete` Stimulus controller to the field with the necessary data attributes.
3. **AJAX Request**: When a user types a non-existing item name, the JavaScript controller makes an AJAX POST request to create the entity.
4. **Entity Creation**: The handles the request, creates the entity, and returns the ID and title. `AutocompleteController`
5. **UI Update**: The Tom-Select field is updated with the newly created entity.

## Usage Example
``` php
// In your controller
$form = $this->createForm(ThreadType::class, $thread, ['larp' => $larp]);

// In your form type
->add('involvedFactions', EntityType::class, [
    'class' => LarpFaction::class,
    'choice_label' => 'title',
    'multiple' => true,
    'autocomplete' => true,
    'tom_select_options' => [
        'create' => true,
        'persist' => false,
    ],
])
```
## Requirements
- Entity must implement `TargetableInterface`
- Entity must have and `setTitle()` methods `getTitle()`
- Entity must have a `setLarp()` method
- Form must pass `larp` option
- Tom-Select options must include `create: true` and `persist: false`

## Troubleshooting
### Entity Not Created
- Check that your entity implements `TargetableInterface`
- Verify that returns the correct enum value `getTargetType()``TargetType`
- Ensure the entity has and `setTitle()` methods `getTitle()`

### Form Extension Not Working
- Verify that the `larp` option is passed to the form
- Check that `tom_select_options` includes both `create: true` and `persist: false`
- Ensure the entity class has the static method `getTargetType()`

### AJAX Request Fails
- Check browser console for JavaScript errors
- Verify the autocomplete route is accessible
- Check that the entity can be persisted (all required fields are set)

## Technical Details
The system uses:
- **Stimulus**: For client-side JavaScript functionality
- **Tom-Select**: For the autocomplete interface
- **Symfony Form Extensions**: For automatic setup of AJAX functionality
- **Doctrine ORM**: For entity persistence

The `custom-autocomplete` Stimulus controller intercepts the Tom-Select event and makes an AJAX request to the backend, which creates the entity and returns the necessary data for the form field. `create`
