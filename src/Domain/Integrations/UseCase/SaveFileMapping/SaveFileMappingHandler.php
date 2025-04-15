<?php

namespace App\Domain\Integrations\UseCase\SaveFileMapping;

use App\Entity\ObjectFieldMapping;
use App\Enum\FileMappingType;
use App\Repository\LarpRepository;
use App\Repository\ObjectFieldMappingRepository;
use App\Repository\SharedFileRepository;

final readonly class SaveFileMappingHandler
{
    public function __construct(
        private LarpRepository $larpRepository,
        private SharedFileRepository $sharedFileRepository,
        private ObjectFieldMappingRepository $mappingRepository
    ) {}

    public function __invoke(SaveFileMappingCommand $command): void
    {
        $larp = $this->larpRepository->find($command->larpId);
        $sharedFile = $this->sharedFileRepository->find($command->sharedFileId);

        $existing = $this->mappingRepository->findOneBy([
            'larp' => $larp,
            'externalFile' => $sharedFile,
        ]);

        $mapping = $existing ?? new ObjectFieldMapping();
        $mapping->setLarp($larp);
        $mapping->setExternalFile($sharedFile);
        $mapping->setMappingConfiguration($command->fields);
        $mapping->setFileType(FileMappingType::tryFrom($command->mappingType));
        $this->mappingRepository->save($mapping);
    }
}