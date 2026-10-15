<?php

namespace App\Domain\Integrations\Miro\Service;

use App\Domain\Integrations\DTO\RemoteSource;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class MiroBoardSelection
{
    public function __construct(
        private ExternalSourceRepository $externalSourceRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<string>
     */
    public function selectedBoardIds(LarpIntegration $integration): array
    {
        $selectedBoards = $this->externalSourceRepository->findBy([
            'integration' => $integration,
            'kind' => ExternalSourceKind::MiroBoard,
            'enabled' => true,
        ]);

        return array_values(array_map(static fn (ExternalSource $board): string => $board->getExternalId(), $selectedBoards));
    }

    public function select(LarpIntegration $integration, RemoteSource $board): ExternalSource
    {
        $boardSource = $this->externalSourceRepository->findOneByExternalId($integration, $board->externalId);
        if (!$boardSource instanceof ExternalSource) {
            $boardSource = new ExternalSource();
            $boardSource->setIntegration($integration);
            $boardSource->setKind(ExternalSourceKind::MiroBoard);
            $boardSource->setExternalId($board->externalId);
            $this->entityManager->persist($boardSource);
        }

        $boardSource->setTitle($board->name);
        $boardSource->setUrl($board->url);
        $boardSource->setEnabled(true);
        $this->entityManager->flush();

        return $boardSource;
    }

    public function deselect(LarpIntegration $integration, string $boardId): void
    {
        $boardSource = $this->externalSourceRepository->findOneByExternalId($integration, $boardId);
        if (!$boardSource instanceof ExternalSource) {
            return;
        }

        $boardSource->setEnabled(false);
        $this->entityManager->flush();
    }
}
