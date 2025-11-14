<?php

namespace App\Domain\Gallery\Controller;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Gallery\Entity\Gallery;
use App\Domain\Gallery\Form\Filter\GalleryFilterType;
use App\Domain\Gallery\Form\GalleryType;
use App\Domain\Gallery\Repository\GalleryRepository;
use App\Domain\Gallery\Security\Voter\LarpGalleryVoter;
use App\Domain\Gallery\Service\GalleryFileService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/gallery', name: 'backoffice_larp_gallery_')]
#[IsGranted('ROLE_USER')]
class GalleryController extends BaseController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, Larp $larp, GalleryRepository $repository): Response
    {
        $filterForm = $this->createForm(GalleryFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('g')
            ->addSelect('photographer', 'user')
            ->join('g.photographer', 'photographer')
            ->join('photographer.user', 'user')
            ->where('g.larp = :larp')
            ->setParameter('larp', $larp);

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Apply sorting
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        match ($sortBy) {
            'title' => $qb->orderBy('g.title', $sortOrder),
            'photographer' => $qb->orderBy('user.username', $sortOrder),
            'visibility' => $qb->orderBy('g.visibility', $sortOrder),
            default => $qb->orderBy('g.createdAt', $sortOrder),
        };

        $pagination = $this->getPagination($qb, $request);

        return $this->render('domain/gallery/list.html.twig', [
            'larp' => $larp,
            'galleries' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, Larp $larp, GalleryRepository $repository, GalleryFileService $galleryFileService): Response
    {
        $this->denyAccessUnlessGranted(LarpGalleryVoter::CREATE, $larp);

        $gallery = new Gallery();
        $gallery->setLarp($larp);

        $form = $this->createForm(GalleryType::class, $gallery, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $zipFile */
            $zipFile = $form->get('zipFileUpload')->getData();

            if ($zipFile) {
                try {
                    $filename = $galleryFileService->uploadZipFile($gallery, $zipFile);
                    $gallery->setZipFile($filename);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('gallery.upload_error'));
                }
            }

            $repository->save($gallery);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_gallery_list', ['larp' => $larp->getId()]);
        }

        return $this->render('domain/gallery/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'gallery' => null,
        ]);
    }

    #[Route('/{gallery}', name: 'view', methods: ['GET'])]
    public function view(Larp $larp, Gallery $gallery): Response
    {
        $this->denyAccessUnlessGranted(LarpGalleryVoter::VIEW, $gallery);

        return $this->render('domain/gallery/view.html.twig', [
            'larp' => $larp,
            'gallery' => $gallery,
        ]);
    }

    #[Route('/{gallery}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Larp $larp, Gallery $gallery, GalleryRepository $repository, GalleryFileService $galleryFileService): Response
    {
        $this->denyAccessUnlessGranted(LarpGalleryVoter::EDIT, $gallery);

        $form = $this->createForm(GalleryType::class, $gallery, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $zipFile */
            $zipFile = $form->get('zipFileUpload')->getData();

            if ($zipFile) {
                // Delete old file if exists
                if ($gallery->getZipFile()) {
                    $galleryFileService->deleteZipFile($gallery);
                }

                try {
                    $filename = $galleryFileService->uploadZipFile($gallery, $zipFile);
                    $gallery->setZipFile($filename);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('gallery.upload_error'));
                }
            }

            $repository->save($gallery);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_gallery_list', ['larp' => $larp->getId()]);
        }

        return $this->render('domain/gallery/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'gallery' => $gallery,
        ]);
    }

    #[Route('/{gallery}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Larp $larp, Gallery $gallery, GalleryRepository $repository, GalleryFileService $galleryFileService): Response
    {
        $this->denyAccessUnlessGranted(LarpGalleryVoter::DELETE, $gallery);

        // Delete uploaded files
        $galleryFileService->deleteGalleryDirectory($gallery);

        $repository->remove($gallery);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_gallery_list', ['larp' => $larp->getId()]);
    }
}
