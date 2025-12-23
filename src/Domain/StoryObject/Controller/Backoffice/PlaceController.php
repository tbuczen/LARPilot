<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\LarpManager;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\StoryObject\Entity\Place;
use App\Domain\StoryObject\Form\Filter\PlaceFilterType;
use App\Domain\StoryObject\Form\PlaceType;
use App\Domain\StoryObject\Repository\PlaceRepository;
use App\Domain\StoryObject\Service\StoryObjectMentionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/place/', name: 'backoffice_larp_story_place_')]
class PlaceController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, PlaceRepository $repository): Response
    {
        $filterForm = $this->createForm(PlaceFilterType::class);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')->setParameter('larp', $larp);

        return $this->render('backoffice/larp/place/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'places' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{place}', name: 'modify', defaults: ['place' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager $larpManager,
        IntegrationManager $integrationManager,
        StoryObjectMentionService $mentionService,
        Request $request,
        Larp $larp,
        PlaceRepository $placeRepository,
        ?Place $place = null,
    ): Response {
        $new = false;
        if (!$place instanceof Place) {
            $place = new Place();
            $place->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(PlaceType::class, $place, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $placeRepository->save($place);
            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $place);
            $this->addFlash('success', $this->translator->trans('success_save'));
            return $this->redirectToRoute('backoffice_larp_story_place_list', ['larp' => $larp->getId()]);
        }

        // Get mentions only for existing places (not new ones)
        $mentions = [];
        if (!$new) {
            $mentions = $mentionService->findMentions($place);
        }

        return $this->render('backoffice/larp/place/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'place' => $place,
            'mentions' => $mentions,
        ]);
    }

    #[Route('{place}/mentions', name: 'mentions', methods: ['GET'])]
    public function mentions(
        Larp                      $larp,
        Place                     $place,
        StoryObjectMentionService $mentionService,
    ): Response {
        $mentions = $mentionService->findMentions($place);

        return $this->render('backoffice/larp/place/mentions.html.twig', [
            'larp' => $larp,
            'place' => $place,
            'mentions' => $mentions,
        ]);
    }

    #[Route('{place}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager $larpManager,
        IntegrationManager $integrationManager,
        Larp $larp,
        Request $request,
        PlaceRepository $placeRepository,
        Place $place,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');
        if ($deleteIntegrations && !$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $place, 'Place')) {
            return $this->redirectToRoute('backoffice_larp_story_place_list', ['larp' => $larp->getId()]);
        }

        $placeRepository->remove($place);
        $this->addFlash('success', $this->translator->trans('success_delete'));
        return $this->redirectToRoute('backoffice_larp_story_place_list', ['larp' => $larp->getId()]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}
