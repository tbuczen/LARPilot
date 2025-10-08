<?php

namespace App\Domain\Larp\UseCase\SubmitLarp;

use App\Domain\Larp\DTO\SubmitLarpDTO;
use App\Entity\Enum\LarpStageStatus;
use App\Entity\Enum\UserRole;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\User;
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
        if (!$user instanceof \App\Entity\User) {
            throw new \RuntimeException('User not found.');
        }
        $participant->setUser($user);
        $participant->setLarp($larp);
        $participant->setRoles([UserRole::ORGANIZER->value]);

        $this->entityManager->persist($participant);
    }
}
