<?php

namespace App\Service\Larp;

use App\Entity\Enum\LarpIntegrationProvider;
use App\Entity\Larp;
use App\Entity\LarpIntegration;
use App\Entity\LarpInvitation;
use App\Entity\LarpParticipant;
use App\Entity\User;
use App\Repository\LarpIntegrationRepository;
use App\Repository\LarpInvitationRepository;
use App\Repository\LarpParticipantRepository;
use App\Repository\LarpRepository;
use Symfony\Component\Security\Core\User\UserInterface;

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
