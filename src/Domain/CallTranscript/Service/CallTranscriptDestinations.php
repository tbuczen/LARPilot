<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Discord\Picker\DiscordSourceConfig;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Entity\SharedFile;
use App\Domain\Integrations\Enum\IntegrationFileType;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Repository\ObjectFieldMappingRepository;

readonly class CallTranscriptDestinations
{
    public function __construct(
        private LarpIntegrationRepository $integrationRepository,
        private ObjectFieldMappingRepository $mappingRepository,
        private ExternalSourceRepository $sourceRepository,
    ) {
    }

    public function discordIntegration(Larp $larp): ?LarpIntegration
    {
        $integration = $this->integrationRepository->findOneByLarpAndProvider($larp, LarpIntegrationProvider::Discord);

        return $integration instanceof LarpIntegration && $integration->getStatus()->isUsable() ? $integration : null;
    }

    /**
     * @return list<ExternalSource>
     */
    public function discordChannels(Larp $larp): array
    {
        $integration = $this->discordIntegration($larp);
        if ($integration === null) {
            return [];
        }

        return array_values(array_filter(
            $this->sourceRepository->findForIntegration($integration),
            static fn (ExternalSource $source): bool => $source->isEnabled()
                && in_array($source->getKind(), [ExternalSourceKind::DiscordChannel, ExternalSourceKind::DiscordForum], true),
        ));
    }

    public function discordChannel(Larp $larp, ?string $channelId): ?ExternalSource
    {
        foreach ($this->discordChannels($larp) as $source) {
            if ($source->getExternalId() === $channelId) {
                return $source;
            }
        }

        return null;
    }

    public static function isPlayerFacing(ExternalSource $source): bool
    {
        return DiscordSourceConfig::fromArray($source->getConfig())->visibility === ExternalSourceVisibility::PlayerFacing;
    }

    public function googleIntegration(Larp $larp): ?LarpIntegration
    {
        $integration = $this->integrationRepository->findOneByLarpAndProvider($larp, LarpIntegrationProvider::Google);

        return $integration instanceof LarpIntegration && $integration->getStatus()->isUsable() ? $integration : null;
    }

    /**
     * @return list<SharedFile>
     */
    public function googleDocFolders(Larp $larp): array
    {
        $integration = $this->googleIntegration($larp);
        if ($integration === null) {
            return [];
        }

        $folders = [];
        foreach ($integration->getSharedFiles() as $sharedFile) {
            if (IntegrationFileType::fromMimeType($sharedFile->getMimeType()) === IntegrationFileType::FOLDER) {
                $folders[] = $sharedFile;
            }
        }

        usort($folders, static fn (SharedFile $left, SharedFile $right): int => strcasecmp($left->getFileName(), $right->getFileName()));

        return $folders;
    }

    public function googleDocFolder(Larp $larp, ?string $sharedFileId): ?SharedFile
    {
        foreach ($this->googleDocFolders($larp) as $folder) {
            if ($folder->getId()->toRfc4122() === $sharedFileId) {
                return $folder;
            }
        }

        return null;
    }

    public function defaultGoogleDocFolder(Larp $larp): ?SharedFile
    {
        $mapping = $this->defaultGoogleDocFolderMapping($larp);
        $folder = $mapping?->getExternalFile();
        if ($folder === null) {
            return null;
        }

        return $this->googleDocFolder($larp, $folder->getId()->toRfc4122());
    }

    public function saveDefaultGoogleDocFolder(Larp $larp, ?SharedFile $folder, User $organizer): void
    {
        $mapping = $this->defaultGoogleDocFolderMapping($larp);
        if ($folder === null) {
            if ($mapping !== null) {
                $this->mappingRepository->remove($mapping);
            }

            return;
        }

        if ($mapping === null) {
            $mapping = (new ObjectFieldMapping())->setLarp($larp)->setFileType(ResourceType::CALL_TRANSCRIPT_DIRECTORY);
            $mapping->setCreatedBy($organizer);
        }

        $mapping->setExternalFile($folder);
        $this->mappingRepository->save($mapping);
    }

    private function defaultGoogleDocFolderMapping(Larp $larp): ?ObjectFieldMapping
    {
        return $this->mappingRepository->findOneBy(['larp' => $larp, 'fileType' => ResourceType::CALL_TRANSCRIPT_DIRECTORY]);
    }
}
