<?php

namespace App\Domain\Core\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Form\LarpType;
use App\Domain\Core\Service\LarpManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/larp/create', name: 'backoffice_larp_create', methods: ['GET', 'POST'])]
class LarpCreateController extends AbstractController
{
    public function __invoke(Request $request, LarpManager $larpManager, SluggerInterface $slugger): Response
    {
        $larp = new Larp();
        $form = $this->createForm(LarpType::class, $larp);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Larp $larp */
            $larp = $form->getData();
            $larp->setStatus(LarpStageStatus::DRAFT);

            // Handle header image file upload
            /** @var UploadedFile|null $headerImageFile */
            $headerImageFile = $form->get('headerImageFile')->getData();
            if ($headerImageFile) {
                $originalFilename = pathinfo($headerImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $headerImageFile->guessExtension();

                try {
                    $headerImageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/larps',
                        $newFilename
                    );
                    $larp->setHeaderImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload header image: ' . $e->getMessage());
                }
            }

            /** @var User $user */
            $user = $this->getUser();
            $larp = $larpManager->createLarp($larp, $user);

            $this->addFlash('success', 'Core created as DRAFT.');

            return $this->redirectToRoute('backoffice_larp_dashboard', ['larp' => $larp->getId()->toRfc4122()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Validation error occurred');
        }

        return $this->render('backoffice/larp/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
