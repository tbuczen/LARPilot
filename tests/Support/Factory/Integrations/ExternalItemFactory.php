<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Integrations;

use App\Domain\Integrations\Entity\Enum\ExternalItemKind;
use App\Domain\Integrations\Entity\ExternalItem;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ExternalItem>
 */
final class ExternalItemFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return ExternalItem::class;
    }

    protected function defaults(): array
    {
        return [
            'source' => ExternalSourceFactory::new(),
            'externalId' => self::faker()->uuid(),
            'kind' => ExternalItemKind::Thread,
            'title' => self::faker()->sentence(),
            'excerpt' => self::faker()->paragraph(),
            'author' => self::faker()->userName(),
            'url' => self::faker()->url(),
            'externalUpdatedAt' => new \DateTimeImmutable('-1 day'),
            'lastSeenAt' => new \DateTimeImmutable(),
        ];
    }
}
