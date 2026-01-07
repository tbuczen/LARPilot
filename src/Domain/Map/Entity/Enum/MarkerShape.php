<?php

declare(strict_types=1);

namespace App\Domain\Map\Entity\Enum;

use App\Domain\Core\Entity\Enum\LabelableEnumInterface;

enum MarkerShape: string implements LabelableEnumInterface
{
    case DOT = 'dot';
    case CIRCLE = 'circle';
    case SQUARE = 'square';
    case DIAMOND = 'diamond';
    case TRIANGLE = 'triangle';
    case HOUSE = 'house';
    case ARROW_UP = 'arrow_up';
    case ARROW_DOWN = 'arrow_down';
    case ARROW_LEFT = 'arrow_left';
    case ARROW_RIGHT = 'arrow_right';
    case STAR = 'star';
    case FLAG = 'flag';
    case PIN = 'pin';
    case CROSS = 'cross';

    public function getLabel(): string
    {
        return match ($this) {
            self::DOT => 'Dot',
            self::CIRCLE => 'Circle',
            self::SQUARE => 'Square',
            self::DIAMOND => 'Diamond',
            self::TRIANGLE => 'Triangle',
            self::HOUSE => 'House',
            self::ARROW_UP => 'Arrow Up',
            self::ARROW_DOWN => 'Arrow Down',
            self::ARROW_LEFT => 'Arrow Left',
            self::ARROW_RIGHT => 'Arrow Right',
            self::STAR => 'Star',
            self::FLAG => 'Flag',
            self::PIN => 'Pin',
            self::CROSS => 'Cross',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DOT => 'bi-circle-fill',
            self::CIRCLE => 'bi-circle',
            self::SQUARE => 'bi-square-fill',
            self::DIAMOND => 'bi-diamond-fill',
            self::TRIANGLE => 'bi-triangle-fill',
            self::HOUSE => 'bi-house-fill',
            self::ARROW_UP => 'bi-arrow-up',
            self::ARROW_DOWN => 'bi-arrow-down',
            self::ARROW_LEFT => 'bi-arrow-left',
            self::ARROW_RIGHT => 'bi-arrow-right',
            self::STAR => 'bi-star-fill',
            self::FLAG => 'bi-flag-fill',
            self::PIN => 'bi-pin-map-fill',
            self::CROSS => 'bi-x-lg',
        };
    }

    public function getSvgPath(): string
    {
        return match ($this) {
            self::DOT => 'M12,12m-8,0a8,8 0 1,0 16,0a8,8 0 1,0 -16,0',
            self::CIRCLE => 'M12,12m-10,0a10,10 0 1,0 20,0a10,10 0 1,0 -20,0',
            self::SQUARE => 'M2,2h20v20H2z',
            self::DIAMOND => 'M12,2L22,12L12,22L2,12z',
            self::TRIANGLE => 'M12,2L22,22H2z',
            self::HOUSE => 'M12,2L2,10v12h8v-6h4v6h8V10z',
            self::ARROW_UP => 'M12,2L22,14H16v8H8v-8H2z',
            self::ARROW_DOWN => 'M12,22L2,10H8V2h8v8h6z',
            self::ARROW_LEFT => 'M2,12L14,2v6h8v8h-8v6z',
            self::ARROW_RIGHT => 'M22,12L10,22v-6H2V8h8V2z',
            self::STAR => 'M12,2l3,6.5l7,1l-5,5l1.2,7L12,18l-6.2,3.5l1.2-7l-5-5l7-1z',
            self::FLAG => 'M4,2v20h2v-8h12l-4-6l4-6z',
            self::PIN => 'M12,2C8,2 5,5 5,9c0,5 7,13 7,13s7-8 7-13c0-4-3-7-7-7zm0,10c-1.7,0-3-1.3-3-3s1.3-3,3-3s3,1.3,3,3S13.7,12,12,12z',
            self::CROSS => 'M4,8h6V2h4v6h6v4h-6v6h-4v-6H4z',
        };
    }
}
