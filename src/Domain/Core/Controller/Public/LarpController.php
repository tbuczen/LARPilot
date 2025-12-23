<?php

namespace App\Domain\Core\Controller\Public;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Form\Filter\LarpPublicFilterType;
use App\Domain\Core\Repository\LarpInvitationRepository;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Core\Repository\LarpRepository;
use App\Domain\Core\Service\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/', name: 'public_larp_')]
class LarpController extends BaseController
{
    #[Route('/terms', name: 'terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->render('public/terms.html.twig');
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(Request $request, LarpRepository $larpRepository): Response
    {
        $filterForm = $this->createForm(LarpPublicFilterType::class);
        $filterForm->handleRequest($request);
        $qb = $this->getListQueryBuilder($larpRepository, $filterForm, $request);
        $qb = $larpRepository->modifyListQueryBuilderForUser($qb, $this->getUser());
        $pagination = $this->getPagination($qb, $request);

        return $this->render('public/larp/list.html.twig', [
            'larps' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/my/larps', name: 'my_larps', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myLarps(LarpParticipantRepository $participantRepository): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $participants = $participantRepository->findForUserWithCharacters($user);

        return $this->render('public/larp/my_larps.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('/larp/{slug}', name: 'details', methods: ['GET'])]
    public function details(
        string $slug,
        LarpRepository $larpRepository,
        LarpApplicationRepository $applicationRepository
    ): Response {
        $larp = $larpRepository->findOneBy(['slug' => $slug]);
        
        if (!$larp instanceof \App\Domain\Core\Entity\Larp) {
            throw $this->createNotFoundException('LARP not found');
        }
        
        $user = $this->getUser();
        $userIsParticipant = false;
        $userHasApplication = false;
        
        if ($user instanceof User) {
            // Check if user is already a participant
            $userIsParticipant = $larp->getParticipants()->exists(fn ($key, $participant): bool => $participant->getUser() === $user);

            // Check if user already has an application
            $userHasApplication = $applicationRepository->findOneBy(['larp' => $larp, 'user' => $user]) !== null;
        }
        
        return $this->render('public/larp/details.html.twig', [
            'larp' => $larp,
            'userIsParticipant' => $userIsParticipant,
            'userHasApplication' => $userHasApplication,
        ]);
    }

    #[Route('/larp/{slug}/invitation/{code}', name: 'process_invitation', methods: ['GET', 'POST'])]
    public function processInvitation(Request $request, LarpRepository $larpRepository, LarpInvitationRepository $invitationRepository, string $code, string $slug): Response
    {
        $larp = $larpRepository->findOneBy(['slug' => $slug]);

        if ($larp === null) {
            throw $this->createAccessDeniedException();
        }

        $invitation = $invitationRepository->findOneBy(['code' => $code, 'larp' => $larp]);

        if ($invitation === null) {
            throw $this->createAccessDeniedException();
        }

        // Process the invitation (e.g., accept or decline)
        // User has to be logged in, otherwise send to connect page and after registration redirect to this page again

        return $this->render('public/larp/invitation_process.html.twig', [
            'larp' => $larp,
            'invitation' => $invitation,
        ]);
    }


    #[Route('/larp/{slug}/invitation/{code}/accept', name: 'accept_invitation', methods: ['GET', 'POST'])]
    public function acceptInvitation(
        LarpRepository $larpRepository,
        LarpInvitationRepository $invitationRepository,
        string $code,
        string $slug,
        LarpManager $larpManager
    ): Response {
        $larp = $larpRepository->findOneBy(['slug' => $slug]);
        if ($larp === null) {
            throw $this->createAccessDeniedException();
        }

        $invitation = $invitationRepository->findOneBy(['code' => $code, 'larp' => $larp]);
        if ($invitation === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            $larpManager->acceptInvitation($invitation, $currentUser);
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('public_larp_list');
        }

        $this->addFlash('success', $this->translator->trans('public.larp.invitation.accepted'));
        return $this->redirectToRoute('public_larp_details', ['slug' => $invitation->getLarp()->getSlug()]);
    }
}
