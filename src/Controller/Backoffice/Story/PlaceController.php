<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Place;
use App\Form\Filter\PlaceFilterType;
use App\Form\PlaceType;
use App\Helper\Logger;
use App\Repository\StoryObject\PlaceRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
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
        Request $request,
        Larp $larp,
        PlaceRepository $placeRepository,
        ?Place $place = null,
    ): Response {
        $new = false;
        if (!$place) {
            $place = new Place();
            $place->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(PlaceType::class, $place, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $placeRepository->save($place);
            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $place);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_place_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/place/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'place' => $place,
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
        if ($deleteIntegrations) {
            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    $integrationService->removeStoryObject($integration, $place);
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. Place not deleted.');
                    return $this->redirectToRoute('backoffice_larp_story_place_list', [ 'larp' => $larp->getId() ]);
                }
            }
        }

        $placeRepository->remove($place);
        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));
        return $this->redirectToRoute('backoffice_larp_story_place_list', ['larp' => $larp->getId()]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}
