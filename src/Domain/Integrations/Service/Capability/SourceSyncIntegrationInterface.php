<?php

namespace App\Domain\Integrations\Service\Capability;

use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Service\Exceptions\ReAuthenticationNeededException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncForbiddenException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncRateLimitedException;
use App\Domain\Integrations\Service\Exceptions\SourceSyncUnauthorizedException;

interface SourceSyncIntegrationInterface extends IntegrationProviderInterface
{
    /**
     * @param array<string, mixed>|null $cursor
     * @throws SourceSyncRateLimitedException
     * @throws SourceSyncUnauthorizedException
     * @throws SourceSyncForbiddenException
     * @throws ReAuthenticationNeededException
     */
    public function fetchSourcePage(ExternalSource $source, ?array $cursor): SourceSyncPage;
}
