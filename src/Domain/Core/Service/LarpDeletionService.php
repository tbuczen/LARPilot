<?php

namespace App\Domain\Core\Service;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Event\LarpDeletedEvent;
use App\Domain\Core\Event\LarpDeletingEvent;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\IntegrationRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

class LarpDeletionService
{
    private const STORY_OBJECTS = 'SELECT id FROM story_object WHERE larp_id = :larp';

    private const STATEMENTS = [
        'UPDATE "character" SET larp_participant_id = NULL, story_writer_id = NULL, continuation_character_id = NULL, previous_character_id = NULL WHERE id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE "character" SET continuation_character_id = NULL WHERE continuation_character_id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE "character" SET previous_character_id = NULL WHERE previous_character_id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE item SET designation_id = NULL WHERE designation_id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE event SET place_id = NULL WHERE place_id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE event SET thread_id = NULL WHERE thread_id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE quest SET thread_id = NULL WHERE thread_id IN (' . self::STORY_OBJECTS . ')',
        'UPDATE larp_participant SET larp_application_id = NULL WHERE larp_id = :larp',
        'DELETE FROM larp_application_vote WHERE choice_id IN (SELECT c.id FROM larp_application_choice c JOIN larp_application a ON a.id = c.application_id WHERE a.larp_id = :larp OR c.character_id IN (' . self::STORY_OBJECTS . '))',
        'DELETE FROM larp_application_choice WHERE application_id IN (SELECT id FROM larp_application WHERE larp_id = :larp) OR character_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM larp_application WHERE larp_id = :larp',
        'DELETE FROM recruitment_proposal WHERE recruitment_id IN (SELECT id FROM story_recruitment WHERE story_object_id IN (' . self::STORY_OBJECTS . ')) OR character_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM story_recruitment WHERE story_object_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM character_skill WHERE character_id IN (' . self::STORY_OBJECTS . ') OR skill_id IN (SELECT id FROM skill WHERE larp_id = :larp)',
        'DELETE FROM character_item WHERE character_id IN (' . self::STORY_OBJECTS . ') OR item_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM external_reference WHERE larp_id = :larp OR story_object_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM larp_invitation WHERE larp_id = :larp OR larp_character_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM survey_answer WHERE response_id IN (SELECT id FROM survey_response WHERE larp_id = :larp OR survey_id IN (SELECT id FROM survey WHERE larp_id = :larp))',
        'DELETE FROM survey_response WHERE larp_id = :larp OR survey_id IN (SELECT id FROM survey WHERE larp_id = :larp)',
        'DELETE FROM survey_question_option WHERE question_id IN (SELECT q.id FROM survey_question q JOIN survey s ON s.id = q.survey_id WHERE s.larp_id = :larp)',
        'DELETE FROM survey_question WHERE survey_id IN (SELECT id FROM survey WHERE larp_id = :larp)',
        'DELETE FROM survey WHERE larp_id = :larp',
        'DELETE FROM map_location WHERE map_id IN (SELECT id FROM game_map WHERE larp_id = :larp) OR place_id IN (' . self::STORY_OBJECTS . ')',
        'DELETE FROM game_map WHERE larp_id = :larp',
        'DELETE FROM kanban_task WHERE larp_id = :larp',
        'DELETE FROM object_field_mapping WHERE larp_id = :larp',
        'DELETE FROM shared_file WHERE integration_id IN (SELECT id FROM larp_integration WHERE larp_id = :larp)',
        'DELETE FROM larp_integration WHERE larp_id = :larp',
        'DELETE FROM story_object_log_entry WHERE object_id IN (SELECT CAST(id AS VARCHAR) FROM story_object WHERE larp_id = :larp)',
        'DELETE FROM story_object WHERE id IN (SELECT id FROM relation WHERE from_id IN (' . self::STORY_OBJECTS . ') OR to_id IN (' . self::STORY_OBJECTS . '))',
        'DELETE FROM story_object WHERE larp_id = :larp',
        'DELETE FROM larp_incident WHERE larp_id = :larp',
        'DELETE FROM scheduled_event WHERE larp_id = :larp',
        'DELETE FROM planning_resource WHERE larp_id = :larp',
        'DELETE FROM gallery WHERE larp_id = :larp',
        'DELETE FROM larp_participant WHERE larp_id = :larp',
        'DELETE FROM call_recording WHERE larp_id = :larp',
        'DELETE FROM knowledge_document WHERE larp_id = :larp',
        'DELETE FROM skill WHERE larp_id = :larp',
        'DELETE FROM tag WHERE larp_id = :larp',
        'DELETE FROM saved_form_filter WHERE larp_id = :larp',
        'DELETE FROM mail_template WHERE larp_id = :larp',
        'DELETE FROM larp WHERE id = :larp',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LarpIntegrationRepository $integrationRepository,
        private readonly IntegrationRegistry $integrationRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDirectory,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function delete(Larp $larp): void
    {
        $larpId = $larp->getId();
        $deletingEvent = new LarpDeletingEvent($larp);
        if ($larp->getHeaderImage() !== null) {
            $deletingEvent->removeAfterDeletion($this->projectDirectory . '/public/uploads/larps/' . basename($larp->getHeaderImage()));
        }
        $this->eventDispatcher->dispatch($deletingEvent);

        $this->disconnectIntegrations($larp);

        $this->entityManager->getConnection()->transactional(function (Connection $connection) use ($larpId): void {
            foreach (self::STATEMENTS as $statement) {
                $connection->executeStatement($statement, ['larp' => $larpId->toRfc4122()]);
            }
        });

        $this->entityManager->clear();

        $this->removeFiles($deletingEvent->getPathsToRemove(), $larpId->toRfc4122());
        $this->notifyDeleted(new LarpDeletedEvent($larpId));
    }

    /**
     * @param list<string> $paths
     */
    private function removeFiles(array $paths, string $larpId): void
    {
        try {
            $this->filesystem->remove($paths);
        } catch (\Throwable $exception) {
            $this->logger->error('Files of a deleted LARP could not be removed.', ['larpId' => $larpId, 'paths' => $paths, 'exception' => $exception]);
        }
    }

    private function notifyDeleted(LarpDeletedEvent $deletedEvent): void
    {
        try {
            $this->eventDispatcher->dispatch($deletedEvent);
        } catch (\Throwable $exception) {
            $this->logger->error('A listener failed after a LARP was deleted.', ['larpId' => $deletedEvent->getLarpId()->toRfc4122(), 'exception' => $exception]);
        }
    }

    private function disconnectIntegrations(Larp $larp): void
    {
        foreach ($this->integrationRepository->findBy(['larp' => $larp]) as $integration) {
            $this->disconnectIntegration($integration);
        }
    }

    private function disconnectIntegration(LarpIntegration $integration): void
    {
        $provider = $integration->getProvider();
        if (!$this->integrationRegistry->supports($provider, ConnectableIntegrationInterface::class)) {
            return;
        }

        $this->integrationRegistry->providerCapability($provider, ConnectableIntegrationInterface::class)->disconnect($integration);
        $this->entityManager->flush();
    }
}
