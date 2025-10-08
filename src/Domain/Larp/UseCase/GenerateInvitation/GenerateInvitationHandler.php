<?php

namespace App\Domain\Larp\UseCase\GenerateInvitation;

use App\Domain\Larp\DTO\GenerateInvitationDTO;
use App\Entity\Larp;
use App\Entity\LarpInvitation;
use Doctrine\ORM\EntityManagerInterface;

readonly class GenerateInvitationHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(GenerateInvitationCommand $command): GenerateInvitationDTO
    {
        $larp = $this->entityManager->getRepository(Larp::class)->find($command->larpId);
        if (!$larp) {
            throw new \RuntimeException('Larp not found.');
        }

        // Create a new Invitation
        $invitation = new LarpInvitation();
        $invitation->setLarp($larp);
        $invitation->setValidTo($command->validTo);

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return new GenerateInvitationDTO(
            invitationCode: $invitation->getCode(),
            larpId: $larp->getId()->toRfc4122(),
            validTo: $invitation->getValidTo()->format('Y-m-d H:i:s'),
            invitedRole: $command->invitedRole->value
        );
    }
}
