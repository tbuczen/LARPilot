<?php

namespace App\Controller\Public;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\LarpCharacterSubmission;
use App\Entity\LarpCharacterSubmissionChoice;
use App\Form\LarpCharacterSubmissionType;
use App\Repository\LarpCharacterSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}', name: 'public_larp_submission_')]
class LarpCharacterSubmissionController extends BaseController
{
    #[Route('/submit', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        Larp $larp,
        LarpCharacterSubmissionRepository $repository,
        EntityManagerInterface $em
    ): Response {
        if (!$larp->getStatus()?->isVisibleForEveryone()) {
            throw $this->createAccessDeniedException();
        }

        $submission = new LarpCharacterSubmission();
        $submission->setLarp($larp);
        $submission->setUser($this->getUser());

        for ($i = 1; $i <= $larp->getMaxCharacterChoices(); ++$i) {
            $choice = new LarpCharacterSubmissionChoice();
            $choice->setPriority($i);
            $submission->addChoice($choice);
        }

        $form = $this->createForm(LarpCharacterSubmissionType::class, $submission, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($submission);
            $this->addFlash('success', 'Submission created');
            return $this->redirectToRoute('public_larp_details', ['slug' => $larp->getSlug()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->showErrorsAsFlash($form->getErrors(true));
        }

        return $this->render('public/larp/submission_form.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }
}
