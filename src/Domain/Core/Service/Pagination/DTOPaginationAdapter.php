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
class DTOPaginationAdapter implements \IteratorAggregate, \Countable, \ArrayAccess
{
    private PaginationInterface $originalPagination;

    /** @var array<mixed> */
    private array $items;

    /**
     * @param PaginationInterface $originalPagination
     * @param array<mixed> $items Transformed DTO items
     */
    private function __construct(
        PaginationInterface $originalPagination,
        array $items
    ) {
        $this->originalPagination = $originalPagination;
        $this->items = $items;
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
     * @return array
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

    /**
     * @return array<string, mixed>
     */
    public function getPaginationData(): array
    {
        if (method_exists($this->originalPagination, 'getPaginationData')) {
            /** @var array<string, mixed> $result */
            $result = $this->originalPagination->getPaginationData();
            return $result;
        }

        return [];
    }

    public function setCustomParameters(array $parameters): void
    {
        $this->originalPagination->setCustomParameters($parameters);
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

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->originalPagination->offsetSet($offset, $value);
    }
}
