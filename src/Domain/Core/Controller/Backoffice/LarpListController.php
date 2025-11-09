<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Repository\LarpRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp', name: 'backoffice_larp_list', methods: ['GET'])]
class LarpListController extends AbstractController
{
    public function __invoke(LarpRepository $larpRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $larps = $larpRepository->findAllWhereParticipating($user);
        $organizerLarpCount = $user->getOrganizerLarpCount();
        $maxLarpsAllowed = $user->getMaxLarpsAllowed();
        $remainingSlots = $user->getRemainingLarpSlots($organizerLarpCount);

        return $this->render('backoffice/larp/list.html.twig', [
            'larps' => $larps,
            'organizerLarpCount' => $organizerLarpCount,
            'maxLarpsAllowed' => $maxLarpsAllowed,
            'remainingSlots' => $remainingSlots,
            'canCreateMore' => $user->canCreateMoreLarps($organizerLarpCount),
        ]);
    }
}
