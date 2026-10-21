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
    public const SERVICE_ACCOUNT_PERMISSION_ID = 'service-account-permission';
    public const EXPORTED_DOCX_BODY = 'exported-docx-bytes';

    /** @var list<array{method: string, path: string, query: array<string, mixed>}> */
    private static array $requests = [];

    private static int $createdFileCount = 0;

    /** @var array<string, string> */
    private static array $serviceAccountRoles = [];

    /** @var array<string, true> */
    private static array $deletedFileIds = [];

    /** @var list<array{method: string, path: string, body: string}> */
    private static array $uploads = [];

    private static ?int $uploadFailureStatus = null;

    public static function reset(): void
    {
        self::$requests = [];
        self::$createdFileCount = 0;
        self::$serviceAccountRoles = [];
        self::$deletedFileIds = [];
        self::$uploads = [];
        self::$uploadFailureStatus = null;
    }

    public static function failUploadsWith(?int $status): void
    {
        self::$uploadFailureStatus = $status;
    }

    /**
     * @return list<array{method: string, path: string, body: string}>
     */
    public static function uploads(): array
    {
        return self::$uploads;
    }

    public static function grantServiceAccountRole(string $fileId, string $role): void
    {
        self::$serviceAccountRoles[$fileId] = $role;
    }

    public static function serviceAccountRole(string $fileId): ?string
    {
        return self::$serviceAccountRoles[$fileId] ?? null;
    }

    /**
     * @return list<array{method: string, path: string, query: array<string, mixed>}>
     */
    public static function requests(): array
    {
        return self::$requests;
    }

    /**
     * @return list<array{method: string, path: string, query: array<string, mixed>}>
     */
    public static function requestsMatching(string $method, string $pathPattern): array
    {
        return array_values(array_filter(
            self::$requests,
            static fn (array $request): bool => $request['method'] === $method && preg_match($pathPattern, $request['path']) === 1
        ));
    }

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
        $method = $request->getMethod();
        $path = rawurldecode($request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::$requests[] = ['method' => $method, 'path' => $path, 'query' => $query];

        $response = match (true) {
            preg_match('#^/v4/spreadsheets/[^/]+/values/#', $path) === 1 => $this->fixtureResponse('sheet_values_characters.json'),
            preg_match('#^/v4/spreadsheets/[^/]+$#', $path) === 1 => $this->fixtureResponse('spreadsheet_characters.json'),
            str_starts_with($path, '/drive/v3/') || str_starts_with($path, '/upload/drive/v3/') => $this->driveResponse($method, $path, (string) $request->getBody()),
            preg_match('#^/v1/documents/([^/:]+):batchUpdate$#', $path, $matches) === 1 => $this->jsonResponse(['documentId' => $matches[1], 'replies' => []]),
            default => throw new \LogicException(sprintf('No Google fixture registered for %s %s', $method, $path)),
        };

        return Create::promiseFor($response);
    }

    private function driveResponse(string $method, string $path, string $requestBody): Response
    {
        if (str_starts_with($path, '/upload/drive/v3/files')) {
            return $this->upload($method, $path, $requestBody);
        }

        if (preg_match('#^/drive/v3/files/([^/]+)/permissions$#', $path, $matches) === 1) {
            return $method === 'GET'
                ? $this->jsonResponse(['permissions' => $this->servicePermissions($matches[1])])
                : $this->recordServiceAccountRole($matches[1], $requestBody);
        }
        if (preg_match('#^/drive/v3/files/([^/]+)/permissions/[^/]+$#', $path, $matches) === 1) {
            return $this->recordServiceAccountRole($matches[1], $requestBody);
        }
        if (preg_match('#^/drive/v3/files/([^/]+)/copy$#', $path) === 1 || ($method === 'POST' && $path === '/drive/v3/files')) {
            return $this->jsonResponse(['id' => sprintf('created-file-%d', ++self::$createdFileCount)]);
        }
        if ($method === 'GET' && $path === '/drive/v3/files') {
            return $this->jsonResponse(['files' => []]);
        }
        if (preg_match('#^/drive/v3/files/([^/]+)/export$#', $path) === 1) {
            return new Response(200, ['Content-Type' => 'application/octet-stream'], self::EXPORTED_DOCX_BODY);
        }
        if (preg_match('#^/(?:upload/)?drive/v3/files/([^/]+)$#', $path, $matches) !== 1) {
            throw new \LogicException(sprintf('No Google Drive fixture registered for %s %s', $method, $path));
        }

        $fileId = $matches[1];

        return match ($method) {
            'DELETE' => $this->deleteFile($fileId),
            'GET' => $this->jsonResponse([
                'id' => $fileId,
                'trashed' => isset(self::$deletedFileIds[$fileId]),
                'webViewLink' => sprintf('https://docs.google.com/document/d/%s/edit', $fileId),
            ]),
            default => $this->jsonResponse(['id' => $fileId]),
        };
    }

    /**
     * @return list<array{id: string, type: string, role: string, emailAddress: string}>
     */
    private function servicePermissions(string $fileId): array
    {
        if (!isset(self::$serviceAccountRoles[$fileId])) {
            return [];
        }

        return [[
            'id' => self::SERVICE_ACCOUNT_PERMISSION_ID,
            'type' => 'user',
            'role' => self::$serviceAccountRoles[$fileId],
            'emailAddress' => self::SERVICE_ACCOUNT_EMAIL,
        ]];
    }

    private function recordServiceAccountRole(string $fileId, string $requestBody): Response
    {
        $permission = json_decode($requestBody, true, flags: JSON_THROW_ON_ERROR);
        self::$serviceAccountRoles[$fileId] = (string) $permission['role'];

        return $this->jsonResponse(['id' => self::SERVICE_ACCOUNT_PERMISSION_ID]);
    }

    private function upload(string $method, string $path, string $requestBody): Response
    {
        if (self::$uploadFailureStatus !== null) {
            return new Response(self::$uploadFailureStatus, ['Content-Type' => 'application/json'], (string) json_encode([
                'error' => ['code' => self::$uploadFailureStatus, 'message' => 'The user has not granted the app access to the folder.'],
            ]));
        }

        self::$uploads[] = ['method' => $method, 'path' => $path, 'body' => $requestBody];
        $fileId = preg_match('#^/upload/drive/v3/files/([^/]+)$#', $path, $matches) === 1 ? $matches[1] : sprintf('created-file-%d', ++self::$createdFileCount);

        return $this->jsonResponse([
            'id' => $fileId,
            'webViewLink' => sprintf('https://docs.google.com/document/d/%s/edit', $fileId),
        ]);
    }

    private function deleteFile(string $fileId): Response
    {
        self::$deletedFileIds[$fileId] = true;

        return new Response(204);
    }

    private function fixtureResponse(string $fixture): Response
    {
        $body = (string) file_get_contents(IntegrationFixtureRouters::fixtureDirectory('google') . '/' . $fixture);

        return new Response(200, ['Content-Type' => 'application/json'], $body);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
