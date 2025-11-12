<?php

namespace App\Domain\SuperAdmin\Controller;

use App\Domain\Account\Entity\Enum\UserStatus;
use App\Domain\Account\Entity\User;
use App\Domain\Account\Repository\PlanRepository;
use App\Domain\Account\Repository\UserRepository;
use App\Domain\Core\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** TODO:: Move logic to separate service */
#[Route('/super-admin/users', name: 'super_admin_users_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class UserManagementController extends BaseController
{
    #[Route('', name: 'list')]
    public function list(Request $request, UserRepository $userRepository): Response
    {
        $qb = $userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        // Handle sorting
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        match ($sortBy) {
            'username' => $qb->orderBy('u.username', $sortOrder),
            'email' => $qb->orderBy('u.contactEmail', $sortOrder),
            'status' => $qb->orderBy('u.status', $sortOrder),
            default => $qb->orderBy('u.createdAt', $sortOrder),
        };

        // Handle status filtering
        $statusFilter = $request->query->get('status');
        if (in_array($statusFilter, ['pending', 'approved', 'suspended', 'banned'])) {
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
    public function edit(User $user, Request $request, PlanRepository $planRepository): Response
    {
        if ($request->isMethod('POST')) {
            $updated = false;

            // Handle status change
            $status = $request->request->get('status');
            if ($status && in_array($status, ['pending', 'approved', 'suspended', 'banned'])) {
                $user->setStatus(UserStatus::from($status));
                $updated = true;
            }

            // Handle plan change
            $planId = $request->request->get('plan');
            if ($planId) {
                if ($planId === 'none') {
                    $user->setPlan(null);
                    $updated = true;
                } else {
                    $plan = $planRepository->find($planId);
                    if ($plan) {
                        $user->setPlan($plan);
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                $this->entityManager->flush();
                $this->addFlash('success', 'User settings updated successfully.');
                return $this->redirectToRoute('super_admin_users_list');
            }
        }

        $plans = $planRepository->findActivePlans();

        return $this->render('super_admin/users/edit.html.twig', [
            'user' => $user,
            'statuses' => UserStatus::cases(),
            'plans' => $plans,
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
