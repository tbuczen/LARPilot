<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountSettingsType;
use App\Repository\UserSocialAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account', name: 'account_')]
class AccountController extends BaseController
{
    #[Route('/', name: 'settings', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user @see security.yaml access_control */
        $user = $this->getUser();

        $form = $this->createForm(AccountSettingsType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('account_settings');
        }

        return $this->render('account/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/social-accounts', name: 'social_accounts', methods: ['GET'])]
    public function socialAccounts(UserSocialAccountRepository $socialAccountRepository): Response
    {
        /** @var User $user @see security.yaml access_control */
        $user = $this->getUser();

        $socialAccounts = $socialAccountRepository->getAllBelongingToUser($user);

        return $this->render('account/social_accounts.html.twig', [
            'socialAccounts' => $socialAccounts,
        ]);
    }

    #[Route('/social-accounts/unlink/{id}', name: 'social_unlink', methods: ['POST'])]
    public function unlink(
        string $id,
        UserSocialAccountRepository $socialAccountRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        /** @var User $user @see security.yaml access_control */
        $user = $this->getUser();

        $socialAccount = $socialAccountRepository->find($id);
        if (!$socialAccount) {
            $this->addFlash('danger', 'Social account not found.');
            return $this->redirectToRoute('account_social_accounts');
        }

        if ($socialAccount->getUser()->getId() !== $user->getId()) {
            $this->addFlash('danger', 'You can only unlink accounts that belong to You.');
            return $this->redirectToRoute('account_social_accounts');
        }

        $linkedAccounts = $socialAccountRepository->getAllBelongingToUser($user);
        if (count($linkedAccounts) <= 1) {
            $this->addFlash('warning', 'You cannot unlink your only social account.');
            return $this->redirectToRoute('account_social_accounts');
        }

        // All checks passed - perform a hard delete
        $entityManager->remove($socialAccount);
        $entityManager->flush();

        $this->addFlash('success', 'Social account unlinked successfully.');
        return $this->redirectToRoute('account_social_accounts');
    }
}
