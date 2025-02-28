<?php

namespace App\Domain\Larp\UseCase\SubmitLarp;

use App\Domain\Larp\DTO\SubmitLarpDTO;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
use App\Enum\LarpStageStatus;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

readonly class SubmitLarpHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(SubmitLarpCommand $command): SubmitLarpDTO
    {
        $larp = new Larp();
        $larp->setName($command->name);
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
            $larp->getName(),
        );
    }

    private function addOrganizer(SubmitLarpCommand $command, Larp $larp): void
    {
        $participant = new LarpParticipant();
        $user = $this->entityManager->getRepository(User::class)->find($command->submittedByUserId);
        if (!$user) {
            throw new \RuntimeException('User not found.');
        }
        $participant->setUser($user);
        $participant->setLarp($larp);
        $participant->setRoles([UserRole::ORGANIZER->value]);

        $this->entityManager->persist($participant);
    }
}
