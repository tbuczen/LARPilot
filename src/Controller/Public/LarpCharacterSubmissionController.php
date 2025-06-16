<?php

namespace App\Controller\Public;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpApplication;
use App\Entity\LarpApplicationChoice;
use App\Form\LarpCharacterSubmissionType;
use App\Repository\LarpApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'public_larp_application_')]
class LarpCharacterSubmissionController extends BaseController
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

        $application = new LarpApplication();
        $application->setLarp($larp);
        $application->setUser($this->getUser());

        for ($i = 1; $i <= $larp->getMaxCharacterChoices(); ++$i) {
            $choice = new LarpApplicationChoice();
            $choice->setPriority($i);
            $application->addChoice($choice);
        }

        $form = $this->createForm(LarpCharacterSubmissionType::class, $application, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
}
