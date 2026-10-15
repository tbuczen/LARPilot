<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations;

use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use PHPUnit\Framework\TestCase;

class ExternalItemKindTest extends TestCase
{
    /**
     * @return iterable<string, array{ExternalItemKind, ReferenceType}>
     */
    public static function referenceTypeProvider(): iterable
    {
        yield 'thread' => [ExternalItemKind::Thread, ReferenceType::Thread];
        yield 'forum post' => [ExternalItemKind::ForumPost, ReferenceType::ForumPost];
        yield 'message' => [ExternalItemKind::Message, ReferenceType::Comment];
        yield 'board item' => [ExternalItemKind::BoardItem, ReferenceType::BoardItem];
        yield 'frame' => [ExternalItemKind::Frame, ReferenceType::BoardFrame];
        yield 'connector' => [ExternalItemKind::Connector, ReferenceType::BoardConnector];
    }

    /**
     * @dataProvider referenceTypeProvider
     */
    public function testMapsToReferenceType(ExternalItemKind $itemKind, ReferenceType $expectedReferenceType): void
    {
        $this->assertSame($expectedReferenceType, $itemKind->referenceType());
    }
}
