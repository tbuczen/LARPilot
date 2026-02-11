<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\LarpManager;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\StoryObject\Entity\LoreDocument;
use App\Domain\StoryObject\Form\Filter\LoreDocumentFilterType;
use App\Domain\StoryObject\Form\LoreDocumentType;
use App\Domain\StoryObject\Repository\LoreDocumentRepository;
use App\Domain\StoryObject\Service\StoryObjectMentionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/lore-document/', name: 'backoffice_larp_story_lore_document_')]
class LoreDocumentController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, LoreDocumentRepository $repository): Response
    {
        $filterForm = $this->createForm(LoreDocumentFilterType::class);
        $filterForm->handleRequest($request);

        $qb = $repository->createFilteredQueryBuilder($larp);
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $sort = $request->query->get('sort', 'priority');
        $dir = $request->query->get('dir', 'desc');

        // Handle sorting
        if ($sort === 'category') {
            $qb->orderBy('ld.category', $dir);
        } elseif ($sort === 'title') {
            $qb->orderBy('ld.title', $dir);
        } else {
            $qb->orderBy('ld.priority', $dir)
                ->addOrderBy('ld.title', 'ASC');
        }

        return $this->render('domain/story_object/lore_document/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'loreDocuments' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{loreDocument}', name: 'modify', defaults: ['loreDocument' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager $larpManager,
        IntegrationManager $integrationManager,
        StoryObjectMentionService $mentionService,
        Request $request,
        Larp $larp,
        LoreDocumentRepository $repository,
        ?LoreDocument $loreDocument = null,
    ): Response {
        $new = false;
        if (!$loreDocument instanceof LoreDocument) {
            $loreDocument = new LoreDocument();
            $loreDocument->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(LoreDocumentType::class, $loreDocument, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($loreDocument);
            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $loreDocument);
            $this->addFlash('success', $this->translator->trans('success_save'));
            return $this->redirectToRoute('backoffice_larp_story_loreDocument_list', ['larp' => $larp->getId()]);
        }

        // Get mentions only for existing documents
//        $mentions = [];
//        if (!$new) {
//            $mentions = $mentionService->findMentions($loreDocument);
//        }

        return $this->render('domain/story_object/lore_document/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'loreDocument' => $loreDocument,
//            'mentions' => $mentions,
        ]);
    }

    #[Route('{loreDocument}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager $larpManager,
        IntegrationManager $integrationManager,
        Larp $larp,
        Request $request,
        LoreDocumentRepository $repository,
        LoreDocument $loreDocument,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');
        if ($deleteIntegrations && !$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $loreDocument, 'LoreDocument')) {
            return $this->redirectToRoute('backoffice_larp_story_loreDocument_list', ['larp' => $larp->getId()]);
        }

        $repository->remove($loreDocument);
        $this->addFlash('success', $this->translator->trans('success_delete'));
        return $this->redirectToRoute('backoffice_larp_story_loreDocument_list', ['larp' => $larp->getId()]);
    }
}
