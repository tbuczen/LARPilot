<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Map;

use App\Domain\Map\Entity\GameMap;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<GameMap>
 */
final class GameMapFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return GameMap::class;
    }

    protected function defaults(): array
    {
        return [
            'larp' => LarpFactory::new(),
            'name' => self::faker()->words(3, true),
            'description' => self::faker()->sentence(),
            'gridRows' => 10,
            'gridColumns' => 10,
            'gridOpacity' => 0.5,
            'gridVisible' => true,
            'imageFile' => null,
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
     * Map for a specific LARP
     */
    public function forLarp(mixed $larp): self
    {
        return $this->with([
            'larp' => $larp,
        ]);
    }

    /**
     * Map with a specific name
     */
    public function withName(string $name): self
    {
        return $this->with([
            'name' => $name,
        ]);
    }

    /**
     * Map with a custom grid size
     */
    public function withGridSize(int $rows, int $columns): self
    {
        return $this->with([
            'gridRows' => $rows,
            'gridColumns' => $columns,
        ]);
    }

    /**
     * Map with an image file
     */
    public function withImage(string $imageFile): self
    {
        return $this->with([
            'imageFile' => $imageFile,
        ]);
    }

    /**
     * Map with grid hidden
     */
    public function withHiddenGrid(): self
    {
        return $this->with([
            'gridVisible' => false,
        ]);
    }

    /**
     * Small map (5x5 grid)
     */
    public function small(): self
    {
        return $this->with([
            'gridRows' => 5,
            'gridColumns' => 5,
        ]);
    }

    /**
     * Large map (20x20 grid)
     */
    public function large(): self
    {
        return $this->with([
            'gridRows' => 20,
            'gridColumns' => 20,
        ]);
    }
}
