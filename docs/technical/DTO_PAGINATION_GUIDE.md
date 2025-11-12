# DTO Pagination Guide

This guide explains how to paginate DTO results while maintaining compatibility with KnpPaginator and the `FilterBuilderUpdaterInterface` filter system.

## Problem

When you have a service method that transforms entities to DTOs, you can't directly paginate the DTO array. The pagination must occur at the QueryBuilder level (for optimal SQL queries), then transform the paginated entities.

## Solution 1: Direct Transformation (Recommended)

**Best for**: Simple transformations where you have a 1:1 or 1:many relationship between entities and DTOs.

### Step 1: Create a transformation method in your service

```php
class ApplicationMatchService
{
    /**
     * Transform paginated choices into CharacterMatchDTO array
     * This is used when pagination is applied at QueryBuilder level
     *
     * @param iterable<LarpApplicationChoice> $paginatedChoices
     * @return CharacterMatchDTO[]
     */
    public function transformPaginatedChoicesToDTOs(iterable $paginatedChoices): array
    {
        // Convert iterable to array
        $choices = is_array($paginatedChoices) ? $paginatedChoices : iterator_to_array($paginatedChoices);

        if (empty($choices)) {
            return [];
        }

        // Extract all choice IDs
        $choiceIds = array_map(
            fn(LarpApplicationChoice $c) => $c->getId()->toRfc4122(),
            $choices
        );

        // Load related data in batch (e.g., votes)
        $votesGrouped = $this->choiceRepository->findVotesGroupedByChoice($choiceIds);

        // Build DTOs with the related data
        return $this->transformToDTOs($choices, $votesGrouped);
    }
}
```

### Step 2: Update your controller

```php
public function match(Request $request, Larp $larp): Response
{
    // Create filter form
    $filterForm = $this->createForm(LarpApplicationChoiceFilterType::class, null, ['larp' => $larp]);
    $filterForm->handleRequest($request);

    // Build QueryBuilder with filters
    $qb = $repository->createQueryBuilder('c')
        ->join('c.application', 'a')
        ->join('c.character', 'ch')
        ->where('a.larp = :larp')
        ->setParameter('larp', $larp);

    // Apply filters from form
    $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

    // Add eager loading
    $qb->addSelect('a', 'ch')
        ->leftJoin('a.user', 'user')
        ->addSelect('user');

    // Apply sorting
    $qb->orderBy('ch.title', 'ASC');

    // PAGINATE ENTITIES (not DTOs!)
    $pagination = $this->getPagination($qb, $request);

    // TRANSFORM paginated entities to DTOs
    $dtoResults = $matchService->transformPaginatedChoicesToDTOs($pagination->getItems());

    return $this->render('template.html.twig', [
        'results' => $dtoResults,
        'pagination' => $pagination,  // Pass original pagination for controls
        'filterForm' => $filterForm->createView(),
    ]);
}
```

### Step 3: Use in template

```twig
{% for dto in results %}
    {# Display your DTOs #}
{% endfor %}

{# Pagination controls work with the original pagination object #}
{% include 'includes/pagination.html.twig' with { pagination: pagination } %}
```

## Solution 2: DTOPaginationAdapter (For Complex Cases)

**Best for**: Complex transformations where you need the pagination object itself to contain DTOs, or when you want to maintain the exact same API.

### Usage Example

```php
use App\Domain\Core\Service\Pagination\DTOPaginationAdapter;

public function match(Request $request, Larp $larp): Response
{
    // Build QueryBuilder with filters (same as Solution 1)
    $qb = $repository->createQueryBuilder('c')
        ->where('a.larp = :larp')
        ->setParameter('larp', $larp);

    $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

    // Paginate entities
    $entityPagination = $this->getPagination($qb, $request);

    // Wrap with DTO transformation
    $dtoPagination = DTOPaginationAdapter::wrap(
        $entityPagination,
        fn($entities) => $matchService->transformPaginatedChoicesToDTOs($entities)
    );

    return $this->render('template.html.twig', [
        'pagination' => $dtoPagination,  // Single object for both data and controls
    ]);
}
```

### Template usage

```twig
{% for dto in pagination %}
    {# DTOPaginationAdapter is iterable #}
{% endfor %}

{# Pagination controls work because adapter proxies metadata methods #}
{% include 'includes/pagination.html.twig' with { pagination: pagination } %}
```

## Performance Tips

### 1. Eager Load Relationships in QueryBuilder

Always eager load relationships you'll need in the DTOs:

```php
$qb->addSelect('a', 'ch', 'user')
    ->leftJoin('c.application', 'a')
    ->leftJoin('c.character', 'ch')
    ->leftJoin('a.user', 'user');
```

### 2. Batch Load Related Data

For data not directly joined (e.g., votes), load in batch for all paginated items:

```php
public function transformPaginatedChoicesToDTOs(iterable $choices): array
{
    $choiceIds = array_map(fn($c) => $c->getId(), $choices);

    // Single query for all votes instead of N+1
    $votesGrouped = $this->voteRepository->findByChoices($choiceIds);

    return $this->buildDTOs($choices, $votesGrouped);
}
```

### 3. Use EntityPreloader for Complex Relationships

For deeply nested relationships, use ShipMonk's EntityPreloader:

```php
public function transformPaginatedChoicesToDTOs(iterable $choices): array
{
    // Preload all nested relationships in optimized queries
    $this->entityPreloader->preload($choices, [
        'application.user',
        'character.factions',
        'votes.user',
    ]);

    return $this->buildDTOs($choices);
}
```

## Why This Pattern?

1. **Filters work**: `FilterBuilderUpdaterInterface` operates on QueryBuilder, not arrays
2. **Pagination is efficient**: SQL LIMIT/OFFSET at database level, not in PHP
3. **N+1 queries avoided**: Batch load related data for paginated subset only
4. **Memory efficient**: Only load entities for current page, not all data
5. **Compatible**: Works seamlessly with existing filter forms and pagination templates

## Common Mistakes

### ❌ Don't do this:
```php
// BAD: Loads ALL entities, transforms ALL to DTOs, then paginates in PHP
$allDtos = $matchService->getMatchData($qb);
$pagination = $paginator->paginate($allDtos, $page, $limit);
```

### ✅ Do this instead:
```php
// GOOD: Paginate entities at DB level, transform only current page
$pagination = $this->getPagination($qb, $request);
$dtos = $matchService->transformPaginatedChoicesToDTOs($pagination->getItems());
```

## Real-World Example

See `CharacterSubmissionsController::match()` for a complete implementation:
- Filters with `LarpApplicationChoiceFilterType`
- Sorts by character, priority, or votes
- Paginates at QueryBuilder level
- Transforms paginated entities to `CharacterMatchDTO`
- Batch loads all votes for paginated choices in a single query
- Works with standard `includes/pagination.html.twig` template
