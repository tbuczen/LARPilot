<?php

namespace App\Domain\Core\UseCase\GenerateInvitation;

use App\Domain\Core\DTO\GenerateInvitationDTO;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpInvitation;
use Doctrine\ORM\EntityManagerInterface;

readonly class GenerateInvitationHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(GenerateInvitationCommand $command): GenerateInvitationDTO
    {
        $larp = $this->entityManager->getRepository(Larp::class)->find($command->larpId);
        if (!$larp instanceof Larp) {
            throw new \RuntimeException('Core not found.');
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
