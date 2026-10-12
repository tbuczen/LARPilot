<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Google\GoogleClientFactoryInterface;
use Google\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Tests\Support\Http\IntegrationFixtureRouters;

final class FixtureGoogleClientFactory implements GoogleClientFactoryInterface
{
    public const SERVICE_ACCOUNT_EMAIL = 'larpilot-test@larpilot-test.iam.gserviceaccount.com';
    public const CHARACTERS_SHEET_ID = '1837206251';

    public function createServiceAccountClient(): Client
    {
        $client = new Client();
        $client->setHttpClient(new GuzzleClient(['handler' => HandlerStack::create($this->respondFromFixtures(...))]));

        return $client;
    }

    public function getServiceAccountEmail(): string
    {
        return self::SERVICE_ACCOUNT_EMAIL;
    }

    public function getClientForIntegration(LarpIntegration $integration): Client
    {
        $client = $this->createServiceAccountClient();
        $client->setAccessToken(['access_token' => (string) $integration->getAccessToken(), 'created' => time(), 'expires_in' => 3600]);

        return $client;
    }

    private function respondFromFixtures(RequestInterface $request): PromiseInterface
    {
        $path = rawurldecode($request->getUri()->getPath());
        $fixture = match (true) {
            preg_match('#^/v4/spreadsheets/[^/]+/values/#', $path) === 1 => 'sheet_values_characters.json',
            preg_match('#^/v4/spreadsheets/[^/]+$#', $path) === 1 => 'spreadsheet_characters.json',
            default => throw new \LogicException(sprintf('No Google fixture registered for %s %s', $request->getMethod(), $path)),
        };

        $body = (string) file_get_contents(IntegrationFixtureRouters::fixtureDirectory('google') . '/' . $fixture);

        return Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], $body));
    }
}
