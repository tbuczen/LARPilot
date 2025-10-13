<?php

namespace App\Domain\Core\Service\Pagination;

use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Wrapper for KnpPaginator that allows transforming entity results to DTOs
 * while maintaining pagination metadata.
 *
 * Usage example:
 * ```php
 * $pagination = $this->getPagination($qb, $request);
 * $dtoPagination = DTOPaginationAdapter::wrap($pagination, function($entities) use ($service) {
 *     return $service->transformEntitiesToDTOs($entities);
 * });
 * ```
 */
readonly class DTOPaginationAdapter implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @param array<mixed> $items Transformed DTO items
     */
    private function __construct(
        private PaginationInterface $originalPagination,
        private array $items
    ) {
    }

    /**
     * Wrap a KnpPaginator result with DTO transformation
     *
     * @param PaginationInterface $pagination Original pagination with entities
     * @param callable $transformer Callable that transforms entities to DTOs: function(array $entities): array
     * @return self
     */
    public static function wrap(PaginationInterface $pagination, callable $transformer): self
    {
        $items = $pagination->getItems();
        $dtoItems = $transformer(is_array($items) ? $items : iterator_to_array($items));

        return new self($pagination, $dtoItems);
    }

    /**
     * Get the transformed DTO items
     *
     * @return array<mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    // Delegate pagination metadata to original pagination
    public function getTotalItemCount(): int
    {
        return $this->originalPagination->getTotalItemCount();
    }

    public function getCurrentPageNumber(): int
    {
        return $this->originalPagination->getCurrentPageNumber();
    }

    public function getItemNumberPerPage(): int
    {
        return $this->originalPagination->getItemNumberPerPage();
    }

    public function getPaginationData(): array
    {
        return $this->originalPagination->getPaginationData();
    }

    public function getCustomParameters(): array
    {
        return $this->originalPagination->getCustomParameters();
    }

    public function setCustomParameters(array $parameters): void
    {
        $this->originalPagination->setCustomParameters($parameters);
    }

    public function getRoute(): ?string
    {
        return $this->originalPagination->getRoute();
    }

    public function getParams(): array
    {
        return $this->originalPagination->getParams();
    }

    // IteratorAggregate implementation
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->items);
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Make the adapter compatible with KnpPaginator templates
     * This allows it to be used directly with {% include 'includes/pagination.html.twig' %}
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this->originalPagination, $name)) {
            return $this->originalPagination->$name(...$arguments);
        }

        throw new \BadMethodCallException(sprintf('Method "%s" does not exist', $name));
    }
}
