<?php

namespace App\Domain\Application\Controller\Public;

use App\Domain\Account\Entity\User;
use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Application\Entity\LarpApplicationChoice;
use App\Domain\Application\Form\LarpCharacterSubmissionType;
use App\Domain\Application\Repository\LarpApplicationRepository;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'public_larp_application_')]
class CharacterApplicationController extends BaseController
{
    #[Route('/submit', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        Larp $larp,
        LarpApplicationRepository $repository,
        EntityManagerInterface $em
    ): Response {
        if (!$larp->getStatus()?->isVisibleForEveryone()) {
            throw $this->createAccessDeniedException();
        }

        if ($repository->findOneBy(['larp' => $larp, 'user' => $this->getUser()])) {
            $this->addFlash('error', 'larp.applications.already_submitted');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        $application = new LarpApplication();
        $application->setLarp($larp);
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        $application->setUser($currentUser);

        for ($i = 1; $i <= $larp->getMaxCharacterChoices(); ++$i) {
            $choice = new LarpApplicationChoice();
            $choice->setPriority($i);
            $application->addChoice($choice);
        }

        $form = $this->createForm(LarpCharacterSubmissionType::class, $application, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($application->getChoices()->toArray() as $choice) {
                if (null === $choice->getCharacter()) {
                    $application->removeChoice($choice);
                }
            }

            $repository->save($application);
            $this->addFlash('success', 'Application created');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->showErrorsAsFlash($form->getErrors(true));
        }

        return $this->render('public/larp/application_form.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('/application/{application}/confirm/{character}', name: 'confirm_character', methods: ['GET', 'POST'])]
    public function confirmCharacter(
        Request $request,
        Larp $larp,
        LarpApplication $application,
        Character $character,
        EntityManagerInterface $em
    ): Response {
        // Verify the user owns this application
        if ($application->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('public.application.not_your_application');
        }

        // Verify the application is in OFFERED status
        if ($application->getStatus() !== SubmissionStatus::OFFERED) {
            $this->addFlash('error', 'public.application.not_offered');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        if ($request->isMethod('POST')) {
            // Update application status to CONFIRMED
            $application->setStatus(SubmissionStatus::CONFIRMED);
            $em->flush();

            $this->addFlash('success', 'public.application.character_confirmed');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        return $this->render('public/larp/confirm_character.html.twig', [
            'larp' => $larp,
            'application' => $application,
            'character' => $character,
        ]);
    }

    #[Route('/application/{application}/decline/{character}', name: 'decline_character', methods: ['GET', 'POST'])]
    public function declineCharacter(
        Request $request,
        Larp $larp,
        LarpApplication $application,
        Character $character,
        EntityManagerInterface $em
    ): Response {
        // Verify the user owns this application
        if ($application->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('public.application.not_your_application');
        }

        // Verify the application is in OFFERED status
        if ($application->getStatus() !== SubmissionStatus::OFFERED) {
            $this->addFlash('error', 'public.application.not_offered');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        if ($request->isMethod('POST')) {
            // Update application status to DECLINED
            $application->setStatus(SubmissionStatus::DECLINED);
            $em->flush();

            $this->addFlash('info', 'public.application.character_declined');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        return $this->render('public/larp/decline_character.html.twig', [
            'larp' => $larp,
            'application' => $application,
            'character' => $character,
        ]);
    }
}
