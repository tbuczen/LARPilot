<?php

namespace App\Domain\Core\Service;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpInvitation;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Repository\LarpInvitationRepository;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;

readonly class LarpManager
{
    public function __construct(
        private LarpRepository            $larpRepository,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private LarpInvitationRepository $larpInvitationRepository,
        private LarpParticipantRepository $larpParticipantRepository,
    ) {
    }

    public function getLarp(string $larpId): ?Larp
    {
        return $this->larpRepository->find($larpId);
    }

    public function createLarp(Larp $larp, User $creator): Larp
    {
        // Add creator as organizer
        $participant = new LarpParticipant();
        $participant->setUser($creator);
        $participant->setLarp($larp);
        $participant->setRoles([\App\Domain\Core\Entity\Enum\ParticipantRole::ORGANIZER]);

        $this->larpRepository->save($larp, false);
        $this->larpParticipantRepository->save($participant, true);

        return $larp;
    }

    /**
     * @return LarpIntegration[]
     */
    public function getIntegrationsForLarp(string|Larp $larp): array
    {
        return $this->larpIntegrationRepository->findAllByLarp($larp);
    }

    public function getIntegrationTypeForLarp(string|Larp $larp, LarpIntegrationProvider $integration): ?LarpIntegration
    {
        return $this->larpIntegrationRepository->findOneBy(['larp' => $larp, 'provider' => $integration]);
    }

    public function getLarpCharacters(Larp $larp)
    {
    }

    public function acceptInvitation(LarpInvitation $invitation, ?User $user): void
    {
        if ($invitation->getValidTo() < new \DateTimeImmutable()) {
            throw new \DomainException('Invitation expired.');
        }

        $userId = $user->getId()->toRfc4122();

        if (!$invitation->isReusable()) {
            if (!in_array($invitation->getAcceptedByUserIds(), [null, []], true)) {
                throw new \DomainException('Invitation already used.');
            }
            $invitation->setAcceptedByUserIds([$userId]);
        } else {
            $invitation->addAcceptedByUserId($userId);
        }

        $larp = $invitation->getLarp();
        $participant = new LarpParticipant();
        $participant->setUser($user);
        $participant->setRoles([$invitation->getInvitedRole()]);
        $larp->addParticipant($participant); // assuming you have a participants relation

        $this->larpParticipantRepository->save($participant, false);
        $this->larpInvitationRepository->save($invitation);
    }
}
