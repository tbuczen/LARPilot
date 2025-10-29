<?php

namespace App\Domain\Integrations\Service;

use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\Enum\ReferenceType;
use App\Domain\Integrations\Entity\Enum\ResourceType;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ObjectFieldMapping;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Repository\ObjectFieldMappingRepository;
use App\Domain\Integrations\Service\Google\GoogleClientManager;
use App\Domain\StoryObject\Entity\Character;
use Google\Service\Docs;
use Google\Service\Docs\BatchUpdateDocumentRequest;
use Google\Service\Docs\Document;
use Google\Service\Docs\ReplaceAllTextRequest;
use Google\Service\Docs\Request as DocsRequest;
use Google\Service\Docs\SubstringMatchCriteria;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

readonly class CharacterSheetExportService
{
    public function __construct(
        private GoogleClientManager           $googleClientManager,
        private ObjectFieldMappingRepository $mappingRepository,
        private ExternalReferenceRepository  $referenceRepository,
    ) {
    }

    /**
     * Check if character sheet export is configured for a LARP
     */
    public function isExportConfigured($larp): bool
    {
        $template = $this->getTemplateMapping($larp);
        $directory = $this->getDirectoryMapping($larp);

        return $template !== null && $directory !== null;
    }

    /**
     * Export character sheet to Google Docs based on template
     *
     * @param Character $character The character to export
     * @return array{documentId: string, documentUrl: string, existed: bool}
     * @throws \Exception
     */
    public function exportCharacterSheet(Character $character): array
    {
        $larp = $character->getLarp();

        // Get template and directory mappings
        $template = $this->getTemplateMapping($larp);
        $directory = $this->getDirectoryMapping($larp);

        if (!$template || !$directory) {
            throw new \RuntimeException('Character sheet template or directory not configured for this LARP');
        }

        $integration = $template->getExternalFile()?->getIntegration();
        if (!$integration) {
            throw new \RuntimeException('Template file not properly configured');
        }

        $client = $this->googleClientManager->getClientForIntegration($integration);
        $docsService = new Docs($client);
        $driveService = new Drive($client);

        // Determine target folder and document name
        $targetFolderId = $this->getTargetFolderId($character, $directory, $driveService);
        $documentName = $this->generateDocumentName($character);

        // Check if document already exists
        $existingDocumentId = $this->findExistingDocument($driveService, $targetFolderId, $documentName);

        if ($existingDocumentId) {
            // Update existing document
            $this->updateDocumentContent($docsService, $existingDocumentId, $template, $character);
            $documentUrl = "https://docs.google.com/document/d/{$existingDocumentId}/edit";

            // Create or update external reference
            $this->createOrUpdateExternalReference($character, $existingDocumentId, $documentUrl, $integration->getProvider());

            return [
                'documentId' => $existingDocumentId,
                'documentUrl' => $documentUrl,
                'existed' => true,
            ];
        }

        // Create new document from template
        $newDocumentId = $this->createDocumentFromTemplate(
            $docsService,
            $driveService,
            $template->getExternalFile()->getFileId(),
            $documentName,
            $targetFolderId,
            $character
        );

        $documentUrl = "https://docs.google.com/document/d/{$newDocumentId}/edit";

        // Create or update external reference
        $this->createOrUpdateExternalReference($character, $newDocumentId, $documentUrl, $integration->getProvider());

        return [
            'documentId' => $newDocumentId,
            'documentUrl' => $documentUrl,
            'existed' => false,
        ];
    }

    private function getTemplateMapping($larp): ?ObjectFieldMapping
    {
        return $this->mappingRepository->findOneBy([
            'larp' => $larp,
            'fileType' => ResourceType::CHARACTER_DOC_TEMPLATE,
        ]);
    }

    private function getDirectoryMapping($larp): ?ObjectFieldMapping
    {
        return $this->mappingRepository->findOneBy([
            'larp' => $larp,
            'fileType' => ResourceType::CHARACTER_DOC_DIRECTORY,
        ]);
    }

    private function getTargetFolderId(Character $character, ObjectFieldMapping $directoryMapping, Drive $driveService): string
    {
        $baseFolder = $directoryMapping->getExternalFile()?->getFileId();
        if (!$baseFolder) {
            throw new \RuntimeException('Character document directory not configured');
        }

        $groupByFaction = $directoryMapping->getMappingConfiguration()['groupByFaction'] ?? false;

        if (!$groupByFaction) {
            return $baseFolder;
        }

        // Get first faction or use default
        $factions = $character->getFactions();
        $factionName = 'Unassigned';

        if ($factions->count() > 0) {
            $factionName = $factions->first()->getTitle();
        }

        // Find or create faction folder
        return $this->findOrCreateFolder($driveService, $baseFolder, $factionName);
    }

    private function findOrCreateFolder(Drive $driveService, string $parentFolderId, string $folderName): string
    {
        // Search for existing folder
        $query = sprintf(
            "'%s' in parents and name = '%s' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            $parentFolderId,
            addslashes($folderName)
        );

        $response = $driveService->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
        ]);

        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }

        // Create new folder
        $fileMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId],
        ]);

        $folder = $driveService->files->create($fileMetadata, [
            'fields' => 'id',
        ]);

        return $folder->getId();
    }

    private function generateDocumentName(Character $character): string
    {
        return sprintf('%s - Character Sheet', $character->getTitle());
    }

    private function findExistingDocument(Drive $driveService, string $folderId, string $documentName): ?string
    {
        $query = sprintf(
            "'%s' in parents and name = '%s' and mimeType = 'application/vnd.google-apps.document' and trashed = false",
            $folderId,
            addslashes($documentName)
        );

        $response = $driveService->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id)',
        ]);

        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }

        return null;
    }

    private function createDocumentFromTemplate(
        Docs   $docsService,
        Drive  $driveService,
        string $templateId,
        string $documentName,
        string $targetFolderId,
        Character $character
    ): string {
        // Copy template
        $copiedFile = $driveService->files->copy($templateId, new DriveFile([
            'name' => $documentName,
            'parents' => [$targetFolderId],
        ]));

        $newDocumentId = $copiedFile->getId();

        // Replace placeholders in the new document
        $this->updateDocumentContent($docsService, $newDocumentId, null, $character);

        return $newDocumentId;
    }

    private function updateDocumentContent(Docs $docsService, string $documentId, ?ObjectFieldMapping $template, Character $character): void
    {
        $replacements = $this->buildReplacementMap($character);

        $requests = [];
        foreach ($replacements as $placeholder => $value) {
            $requests[] = new DocsRequest([
                'replaceAllText' => new ReplaceAllTextRequest([
                    'containsText' => new SubstringMatchCriteria([
                        'text' => $placeholder,
                        'matchCase' => false,
                    ]),
                    'replaceText' => $value,
                ]),
            ]);
        }

        if (count($requests) > 0) {
            $batchUpdateRequest = new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]);

            $docsService->documents->batchUpdate($documentId, $batchUpdateRequest);
        }
    }

    /**
     * Build map of placeholders to actual character data
     */
    private function buildReplacementMap(Character $character): array
    {
        $replacements = [
            '<character.name>' => $character->getTitle() ?? '',
            '<character.inGameName>' => $character->getInGameName() ?? '',
            '<character.description>' => $this->stripHtml($character->getDescription()),
            '<character.gender>' => $character->getGender() ?? '',
            '<character.preferredGender>' => $character->getPreferredGender() ?? '',
            '<character.notes>' => $this->stripHtml($character->getNotes()),
            '<character.type>' => $character->getCharacterType()->value,
        ];

        // Relations
        $relationsFrom = $character->getRelationsFrom();
        $relationsTo = $character->getRelationsTo();
        $allRelations = array_merge($relationsFrom->toArray(), $relationsTo->toArray());

        $relationsList = [];
        foreach ($allRelations as $relation) {
            $relatedObject = $relation->getFrom() === $character ? $relation->getTo() : $relation->getFrom();
            $relationsList[] = sprintf(
                '%s: %s',
                $relatedObject->getTitle(),
                $this->stripHtml($relation->getDescription() ?? '')
            );
        }
        $replacements['<character.relations>'] = implode("\n", $relationsList);

        // Factions
        $factionsList = [];
        foreach ($character->getFactions() as $faction) {
            $factionsList[] = sprintf(
                '%s: %s',
                $faction->getTitle(),
                $this->stripHtml($faction->getDescription() ?? '')
            );
        }
        $replacements['<character.factions>'] = implode("\n", $factionsList);

        // Skills
        $skillsList = [];
        foreach ($character->getSkills() as $skill) {
            $skillsList[] = sprintf(
                '%s: %s',
                $skill->getName(),
                $this->stripHtml($skill->getDescription() ?? '')
            );
        }
        $replacements['<character.skills>'] = implode("\n", $skillsList);

        // Items
        $itemsList = [];
        foreach ($character->getItems() as $item) {
            $itemsList[] = sprintf(
                '%s: %s',
                $item->getTitle(),
                $this->stripHtml($item->getDescription() ?? '')
            );
        }
        $replacements['<character.items>'] = implode("\n", $itemsList);

        // Threads
        $threadsList = [];
        foreach ($character->getThreads() as $thread) {
            $threadsList[] = sprintf(
                '%s: %s',
                $thread->getTitle(),
                $this->stripHtml($thread->getDescription() ?? '')
            );
        }
        $replacements['<character.threads>'] = implode("\n", $threadsList);

        // Quests
        $questsList = [];
        foreach ($character->getQuests() as $quest) {
            $questsList[] = sprintf(
                '%s: %s',
                $quest->getTitle(),
                $this->stripHtml($quest->getDescription() ?? '')
            );
        }
        $replacements['<character.quests>'] = implode("\n", $questsList);

        return $replacements;
    }

    /**
     * Create or update external reference for the exported character sheet
     */
    private function createOrUpdateExternalReference(
        Character $character,
        string $documentId,
        string $documentUrl,
        \App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider $provider
    ): void {
        // Check if reference already exists for this character and document type
        $existingReference = $this->referenceRepository->findOneBy([
            'storyObject' => $character,
            'referenceType' => ReferenceType::Document,
            'role' => ReferenceRole::Primary,
        ]);

        if ($existingReference) {
            // Update existing reference
            $existingReference->setExternalId($documentId);
            $existingReference->setUrl($documentUrl);
            $existingReference->setName('Character Sheet');
            $this->referenceRepository->save($existingReference);
        } else {
            // Create new reference
            $reference = new ExternalReference();
            $reference->setStoryObject($character);
            $reference->setProvider($provider);
            $reference->setExternalId($documentId);
            $reference->setReferenceType(ReferenceType::Document);
            $reference->setRole(ReferenceRole::Primary);
            $reference->setName('Character Sheet');
            $reference->setUrl($documentUrl);
            $this->referenceRepository->save($reference);
        }
    }

    private function stripHtml(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        return strip_tags($html);
    }
}
