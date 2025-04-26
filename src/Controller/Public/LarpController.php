<?php

namespace App\Controller\Public;

use App\Entity\Larp;
use App\Entity\LarpInvitation;
use App\Repository\LarpInvitationRepository;
use App\Repository\LarpRepository;
use App\Repository\UserSocialAccountRepository;
use App\Service\Larp\LarpManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/', name: 'public_larp_')]
class LarpController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Route('/terms', name: 'terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->render('public/terms.html.twig', [
        ]);
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(LarpRepository $larpRepository): Response
    {
        $larps = $larpRepository->findAllUpcomingPublished($this->getUser());
        return $this->render('public/larp/list.html.twig', [
            'larps' => $larps,
        ]);
    }

    #[Route('/larp/{slug}', name: 'details', methods: ['GET'])]
    public function details(string $slug, LarpRepository $larpRepository): Response
    {
        $larp = $larpRepository->findOneBy(['slug' => $slug]);
        return $this->render('public/larp/details.html.twig', [
            'larp' => $larp,
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
    ): Response
    {
        $larp = $larpRepository->findOneBy(['slug' => $slug]);
        if ($larp === null) {
            throw $this->createAccessDeniedException();
        }

        $invitation = $invitationRepository->findOneBy(['code' => $code, 'larp' => $larp]);
        if ($invitation === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            $larpManager->acceptInvitation($invitation, $this->getUser());
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('public_larp_list');
        }

        $this->addFlash('success', $this->translator->trans('public.larp.invitation.accepted'));
        return $this->redirectToRoute('public_larp_details', ['slug' => $invitation->getLarp()->getSlug()]);
    }

}
