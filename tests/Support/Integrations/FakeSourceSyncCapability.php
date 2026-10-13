<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Service\Capability\SourceSyncIntegrationInterface;

final class FakeSourceSyncCapability implements SourceSyncIntegrationInterface
{
    /**
     * @var list<SourceSyncPage|\Throwable>
     */
    private array $scriptedResponses;

    /**
     * @var list<array<string, mixed>|null>
     */
    private array $requestedCursors = [];

    public function __construct(SourceSyncPage|\Throwable ...$scriptedResponses)
    {
        $this->scriptedResponses = array_values($scriptedResponses);
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Discord->value;
    }

    public function fetchSourcePage(ExternalSource $source, ?array $cursor): SourceSyncPage
    {
        $this->requestedCursors[] = $cursor;

        $scriptedResponse = array_shift($this->scriptedResponses);
        if ($scriptedResponse === null) {
            throw new \LogicException('No scripted page left for the fake source sync capability.');
        }

        if ($scriptedResponse instanceof \Throwable) {
            throw $scriptedResponse;
        }

        return $scriptedResponse;
    }

    /**
     * @return list<array<string, mixed>|null>
     */
    public function requestedCursors(): array
    {
        return $this->requestedCursors;
    }
}
