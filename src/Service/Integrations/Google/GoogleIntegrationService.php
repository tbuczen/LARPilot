<?php

namespace App\Service\Integrations\Google;

use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Enum\ReferenceType;
use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Entity\ObjectFieldMapping;
use App\Entity\SharedFile;
use App\Entity\StoryObject\StoryObject;
use App\Form\Models\SpreadsheetMappingModel;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpRepository;
use App\Service\Integrations\BaseIntegrationService;
use App\Service\Integrations\Exceptions\DuplicateStoryObjectException;
use App\Service\Integrations\Exceptions\ReAuthenticationNeededException;
use App\Service\Integrations\IntegrationServiceInterface;
use Exception;
use Google\Service\Drive;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

readonly class GoogleIntegrationService extends BaseIntegrationService implements IntegrationServiceInterface
{
    public const GOOGLE_SCOPES = [
        Drive::DRIVE,
        'email'
    ];

    public function __construct(
        private GoogleClientManager   $googleClientManager,
        private UrlGeneratorInterface $urlGenerator,
        private ClientRegistry        $clientRegistry,
        private LarpRepository            $larpRepository,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private GoogleSpreadsheetIntegrationHelper $googleSpreadsheetIntegrationHelper
    ) {
    }

    public function supports(LarpIntegrationProvider $provider): bool
    {
        return $provider === LarpIntegrationProvider::Google;
    }

    /** @see GoogleAuthenticator */
    public function connect(Larp $larp): Response
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient(LarpIntegrationProvider::Google->value);

        /** @see https://developers.google.com/workspace/drive/picker/guides/overview */
        return $client->redirect(
            self::GOOGLE_SCOPES,
            [
                'access_type' => 'offline',
                'prompt' => 'consent',
                'redirect_uri' => $this->urlGenerator->generate('backoffice_larp_connect_integration_check', [
                    'provider' => LarpIntegrationProvider::Google->value,
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function finalizeConnection(string $larpId, AccessTokenInterface $token, ResourceOwnerInterface $user): void
    {
        $this->createGoogleDriveIntegration($token, $user, $larpId);
    }

    /**
     * @throws ReAuthenticationNeededException
     */
    public function getClient(LarpIntegration $integration): object
    {
        return $this->googleClientManager->getClientForIntegration($integration);
    }

    public function fetchSpreadsheetRows(SharedFile $sharedFile, ObjectFieldMapping $mapping): array
    {
        $spreadsheetMapping = SpreadsheetMappingModel::fromEntity($mapping);
        return $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetRows($sharedFile, $spreadsheetMapping);
    }

    public function fetchSpreadsheetSheetIdByName(SharedFile $sharedFile, ObjectFieldMapping $mapping): string
    {
        $spreadsheetMapping = SpreadsheetMappingModel::fromEntity($mapping);
        return $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetSheetIdByName($sharedFile, $spreadsheetMapping);
    }

    public function getExternalFileUrl(LarpIntegration $integration, string $externalFileId): string
    {
        $client = $this->googleClientManager->createServiceAccountClient();
        $drive = new Drive($client);

        try {
            $file = $drive->files->get($externalFileId, ['fields' => 'webViewLink']);
            return $file->getWebViewLink();
        } catch (Exception $e) {
            throw new RuntimeException('Unable to retrieve file URL: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getOwnerNameFromOwner(ResourceOwnerInterface $owner): ?string
    {
        return match (true) {
            $owner instanceof GoogleUser => $owner->getEmail(),
            default => null,
        };
    }

    public function createGoogleDriveIntegration(
        AccessToken $accessToken,
        ResourceOwnerInterface $owner,
        string $larpId
    ): LarpIntegration {
        $larp = $this->larpRepository->find($larpId);
        Assert::notNull($larp);
        $integrationOwnerName = $this->getOwnerNameFromOwner($owner);

        $tokenValues = $accessToken->getValues();
        $grantedScopes = $tokenValues['scope'] ?? null;
        $integration = new LarpIntegration();
        $integration->setProvider(LarpIntegrationProvider::Google);
        $integration->setAccessToken($accessToken->getToken());
        $integration->setRefreshToken($accessToken->getRefreshToken());
        $integration->setExpiresAt((new \DateTime())->setTimestamp($accessToken->getExpires()));
        $integration->setScopes($grantedScopes);
        $integration->setLarp($larp);
        $integration->setOwner($integrationOwnerName);

        $this->larpIntegrationRepository->save($integration);

        return $integration;
    }

    protected function createStoryObjectList(ObjectFieldMapping $mapping, StoryObject $storyObject): void
    {
        $sharedFile = $mapping->getExternalFile();
        Assert::notNull($sharedFile);

        $spreadsheetMapping = SpreadsheetMappingModel::fromEntity($mapping);
        $columnMapping = $spreadsheetMapping->mappings;
        $characterNameField = $columnMapping['name'] ?? null;

        if (!$characterNameField) {
            throw new RuntimeException('No "name" mapping configured.');
        }

        $rows = $this->googleSpreadsheetIntegrationHelper->fetchSpreadsheetRows($sharedFile, $spreadsheetMapping);

        foreach ($rows as $row) {
            if (isset($row[$characterNameField]) && $row[$characterNameField] === $storyObject->getTitle()) {
                // Duplicate found
                throw new DuplicateStoryObjectException($storyObject, $sharedFile->getUrl());
            }
        }

        $newRow = $this->googleSpreadsheetIntegrationHelper->buildSpreadsheetRow($spreadsheetMapping, $storyObject);
        $this->googleSpreadsheetIntegrationHelper->appendRowToSpreadsheet($sharedFile, $spreadsheetMapping, $newRow);
    }

    protected function createStoryObjectDocument(ObjectFieldMapping $mapping, StoryObject $storyObject)
    {
        // TODO: Implement syncStoryObjectDocument() method.
    }

    public function createReferenceUrl(SharedFile $file, ReferenceType $referenceType, int|string $externalId, array $additionalData = []): ?string
    {
        $baseUrl = $file->getUrl();

        $baseUrl = preg_replace('/\?.*/', '', (string) $baseUrl);
        $sheetId = $additionalData['sheetId'] ?? 0; /* @see CharacterController::importFromSelectedMapping */
        return match ($referenceType) {
            ReferenceType::SpreadsheetRow => $baseUrl . "#gid=$sheetId&range=$externalId:$externalId",
            ReferenceType::DocumentParagraph => $baseUrl . "#heading=$externalId",
            default => $baseUrl,
        };
    }
}
