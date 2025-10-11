<?php

namespace App\Domain\Account\UseCase\AddSocialAccountToUser;

use App\Entity\UserSocialAccount;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class AddSocialAccountToUserHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository         $userRepository,
    ) {
    }

    public function handle(AddSocialAccountToUserCommand $command): UserInterface
    {
        $socialAccountRepo = $this->entityManager->getRepository(UserSocialAccount::class);
        $existingAccount = $socialAccountRepo->findOneBy([
            'provider' => $command->provider,
            'providerUserId' => $command->providerUserId,
            'user' => $command->userId,
        ]);
        $user = $this->userRepository->find($command->userId);

        if (!$existingAccount instanceof UserSocialAccount) {
            $socialAccount = new UserSocialAccount();
            $socialAccount->setProvider($command->provider);
            $socialAccount->setProviderUserId($command->providerUserId);
            $socialAccount->setUser($user);
            $socialAccount->setDisplayName($command->displayName);
            $this->entityManager->persist($socialAccount);
            $this->entityManager->flush();
        }

        return $user;
    }
}
