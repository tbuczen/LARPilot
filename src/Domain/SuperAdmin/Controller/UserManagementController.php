<?php

namespace App\Domain\SuperAdmin\Controller;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use App\Domain\Account\Repository\UserRepository;
use App\Domain\Core\Controller\BaseController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/super-admin/users', name: 'super_admin_users_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class UserManagementController extends BaseController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        // Handle sorting
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        match ($sortBy) {
            'username' => $qb->orderBy('u.username', $sortOrder),
            'email' => $qb->orderBy('u.contactEmail', $sortOrder),
            'status' => $qb->orderBy('u.status', $sortOrder),
            'createdAt' => $qb->orderBy('u.createdAt', $sortOrder),
            default => $qb->orderBy('u.createdAt', $sortOrder),
        };

        // Handle status filtering
        $statusFilter = $request->query->get('status');
        if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'suspended', 'banned'])) {
            $qb->andWhere('u.status = :status')
                ->setParameter('status', UserStatus::from($statusFilter));
        }

        $pagination = $this->getPagination($qb, $request);

        return $this->render('super_admin/users/list.html.twig', [
            'users' => $pagination,
            'statuses' => UserStatus::cases(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $status = $request->request->get('status');
            if ($status && in_array($status, ['pending', 'approved', 'suspended', 'banned'])) {
                $user->setStatus(UserStatus::from($status));
                $this->entityManager->flush();

                $this->addFlash('success', 'User status updated successfully.');
                return $this->redirectToRoute('super_admin_users_list');
            }
        }

        return $this->render('super_admin/users/edit.html.twig', [
            'user' => $user,
            'statuses' => UserStatus::cases(),
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(User $user): Response
    {
        $user->setStatus(UserStatus::APPROVED);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('User %s has been approved.', $user->getUsername()));
        return $this->redirectToRoute('super_admin_users_list');
    }

    #[Route('/{id}/suspend', name: 'suspend', methods: ['POST'])]
    public function suspend(User $user): Response
    {
        $user->setStatus(UserStatus::SUSPENDED);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('User %s has been suspended.', $user->getUsername()));
        return $this->redirectToRoute('super_admin_users_list');
    }

    #[Route('/{id}/ban', name: 'ban', methods: ['POST'])]
    public function ban(User $user): Response
    {
        $user->setStatus(UserStatus::BANNED);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('User %s has been banned.', $user->getUsername()));
        return $this->redirectToRoute('super_admin_users_list');
    }
}
