<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Map;

use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Entity\MapLocation;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MapLocation entity
 */
class MapLocationTest extends TestCase
{
    /**
     * @dataProvider gridCoordinatesProvider
     */
    public function testGetGridCoordinatesString(
        int $gridRows,
        int $gridColumns,
        float $positionX,
        float $positionY,
        string $expected
    ): void {
        $map = $this->createMock(GameMap::class);
        $map->method('getGridRows')->willReturn($gridRows);
        $map->method('getGridColumns')->willReturn($gridColumns);

        $location = new MapLocation();
        $location->setMap($map);
        $location->setPositionX($positionX);
        $location->setPositionY($positionY);

        $this->assertSame($expected, $location->getGridCoordinatesString());
    }

    public static function gridCoordinatesProvider(): array
    {
        return [
            '10x10 top-left corner (0%, 0%)' => [10, 10, 0.0, 0.0, 'A1'],
            '10x10 bottom-right corner (99%, 99%)' => [10, 10, 99.0, 99.0, 'J10'],
            '10x10 center (50%, 50%)' => [10, 10, 50.0, 50.0, 'F6'],
            '10x10 first row, second column' => [10, 10, 15.0, 5.0, 'B1'],
            '10x10 last row, first column' => [10, 10, 5.0, 95.0, 'A10'],
            '5x5 center (50%, 50%)' => [5, 5, 50.0, 50.0, 'C3'],
            '5x5 last cell' => [5, 5, 99.0, 99.0, 'E5'],
            '20x20 center (50%, 50%)' => [20, 20, 50.0, 50.0, 'K11'],
            '10x10 exactly at boundary (10%, 10%)' => [10, 10, 10.0, 10.0, 'B2'],
        ];
    }

    public function testGetGridCoordinatesStringWithoutMap(): void
    {
        $location = new MapLocation();
        // No map set

        $this->assertSame('-', $location->getGridCoordinatesString());
    }

    public function testGetGridCoordinatesStringEdgeCases(): void
    {
        $map = $this->createMock(GameMap::class);
        $map->method('getGridRows')->willReturn(10);
        $map->method('getGridColumns')->willReturn(10);

        $location = new MapLocation();
        $location->setMap($map);

        // Test 100% position (should be clamped to last cell)
        $location->setPositionX(100.0);
        $location->setPositionY(100.0);
        $this->assertSame('J10', $location->getGridCoordinatesString());

        // Test exactly at boundary
        $location->setPositionX(0.0);
        $location->setPositionY(0.0);
        $this->assertSame('A1', $location->getGridCoordinatesString());
    }
}
