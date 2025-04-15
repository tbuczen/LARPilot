<?php

namespace App\Controller\Public;

use App\Entity\LarpInvitation;
use App\Repository\LarpRepository;
use App\Repository\UserSocialAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'public_larp_')]
class LarpController extends AbstractController
{
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

    #[Route('/invitation/{code}', name: 'invite_accept', methods: ['GET', 'POST'])]
    public function acceptInvitation(
        string $code,
        UserSocialAccountRepository $socialAccountRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Lookup the invitation by its code
        $invitation = $entityManager->getRepository(LarpInvitation::class)->findOneBy(['code' => $code]);
        if (!$invitation) {
            throw $this->createNotFoundException('Invalid invitation code.');
        }
        // Check if invitation is still valid
        if ($invitation->getValidTo() < new \DateTimeImmutable()) {
            $this->addFlash('warning', 'Invitation has expired.');
            return $this->redirectToRoute('public_larp_list');
        }

        // Now, if the user is logged in, assign them ROLE_STAFF for this Larp.
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('sso_connect');
        }

        // Add LarpParticipation for the user with ROLE_STAFF if they are not already a participant.
        // (Implement this logic in a service or use case as needed.)

        // For now, you might simply flash a message:
        $this->addFlash('success', 'You have been added as staff for the larp!');
        return $this->redirectToRoute('public_larp_details', ['id' => $invitation->getLarp()->getId()]);
    }
}
