<?php

namespace App\Domain\Account\UseCase\RegisterUser;

use App\Domain\Account\DTO\UserDTO;
use App\Entity\User;
use App\Entity\UserSocialAccount;
use App\Repository\UserRepository;
use App\Repository\UserSocialAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class RegisterUserHandler
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private UserRepository          $userRepository,
        private UserSocialAccountRepository $socialAccountRepository
    ) {}

    public function handle(RegisterUserCommand $command): UserInterface
    {
        $socialAccount = $this->socialAccountRepository->findOneBy([
            'provider' => $command->provider,
            'providerUserId' => $command->providerUserId,
        ]);

        if ($socialAccount) {
            return $socialAccount->getUser();
        }

        $user = $this->userRepository->findOneByEmail($command->email);

        if (!$user) {
            $user = new User();
            $user->setContactEmail($command->email);
            // Assign default role (e.g., PLAYER). Adjust to your enum or role structure.
            $user->setRoles(['ROLE_USER']);
            // Optionally set username
            $user->setUsername($command->username ?? $command->email);
            $this->entityManager->persist($user);
        }

        // Create a new social account and link it with the user
        $socialAccount = new UserSocialAccount();
        $socialAccount->setProvider($command->provider);
        $socialAccount->setProviderUserId($command->providerUserId);
        $socialAccount->setUser($user);
        $socialAccount->setDisplayName($command->displayName);
        $this->entityManager->persist($socialAccount);

        $this->entityManager->flush();

        return $user;
    }
}
