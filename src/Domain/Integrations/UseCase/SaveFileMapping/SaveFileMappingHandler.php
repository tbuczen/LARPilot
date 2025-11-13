<?php

namespace App\Domain\Integrations\UseCase\SaveFileMapping;

use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Repository\ObjectFieldMappingRepository;
use App\Domain\Integrations\Repository\SharedFileRepository;

final readonly class SaveFileMappingHandler
{
    public function __construct(
        private LarpRepository $larpRepository,
        private SharedFileRepository $sharedFileRepository,
        private ObjectFieldMappingRepository $mappingRepository
    ) {
    }

    public function __invoke(SaveFileMappingCommand $command): void
    {
        $larp = $this->larpRepository->find($command->larpId);
        $sharedFile = $this->sharedFileRepository->find($command->sharedFileId);
        $fileType = ResourceType::tryFrom($command->mappingType);

        $existing = $this->mappingRepository->findOneBy([
            'larp' => $larp,
            'externalFile' => $sharedFile,
            'fileType' => $fileType,
        ]);

        $mapping = $existing ?? new ObjectFieldMapping();
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setMappingConfiguration($command->fields);
        $mapping->setMetaConfiguration($command->meta);
        $mapping->setFileType($fileType);
        $this->mappingRepository->save($mapping);
    }
}
