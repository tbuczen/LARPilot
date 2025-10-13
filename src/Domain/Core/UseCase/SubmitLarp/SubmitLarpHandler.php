<?php

namespace App\Domain\Core\UseCase\SubmitLarp;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\DTO\SubmitLarpDTO;
use Doctrine\ORM\EntityManagerInterface;

readonly class SubmitLarpHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(SubmitLarpCommand $command): SubmitLarpDTO
    {
        $larp = new Larp();
        $larp->setTitle($command->name);
        $larp->setDescription($command->description);
        if ($command->location !== null) {
            $larp->setLocation($command->location);
        }
        $larp->setStartDate($command->startDate);
        $larp->setEndDate($command->endDate);
        $larp->setStatus(LarpStageStatus::DRAFT);

        $this->entityManager->persist($larp);
        $this->addOrganizer($command, $larp);

        $this->entityManager->flush();

        return new SubmitLarpDTO(
            $larp->getId()->toRfc4122(),
            $larp->getStatus()->value,
            $larp->getTitle(),
        );
    }

    private function addOrganizer(SubmitLarpCommand $command, Larp $larp): void
    {
        $participant = new LarpParticipant();
        $user = $this->entityManager->getRepository(User::class)->find($command->submittedByUserId);
        if (!$user instanceof User) {
            throw new \RuntimeException('User not found.');
        }
        $participant->setUser($user);
        $participant->setLarp($larp);
        $participant->setRoles([ParticipantRole::ORGANIZER->value]);

        $this->entityManager->persist($participant);
    }
}
