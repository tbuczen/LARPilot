<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\LarpFaction;
use App\Form\FactionType;
use App\Form\Filter\LarpFactionFilterType;
use App\Helper\Logger;
use App\Repository\StoryObject\LarpFactionRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/faction/', name: 'backoffice_larp_story_faction_')]

class LarpFactionsController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET'])]
    public function factions(Request $request, Larp $larp, LarpFactionRepository $repository): Response
    {
        $filterForm = $this->createForm(LarpFactionFilterType::class);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');

        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);

        return $this->render('backoffice/larp/factions/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'factions' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{faction}', name: 'modify', defaults: ['faction' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Request                 $request,
        Larp                    $larp,
        LarpFactionRepository $factionRepository,
        ?LarpFaction          $faction = null,
    ): Response {
        $new = false;
        if (!$faction) {
            $faction = new LarpFaction();
            $faction->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(FactionType::class, $faction, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $factionRepository->save($faction);

            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    if ($new) {
                        $integrationService->createStoryObject($integration, $faction);
                    } else {
                        $integrationService->syncStoryObject($integration, $faction);
                    }
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('warning', 'Failed to sync with ' . $integration->getProvider()->name);
                }
            }

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_faction_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/factions/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'faction' => $faction,
        ]);
    }

    #[Route('{faction}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Larp                    $larp,
        Request                 $request,
        LarpFactionRepository $factionRepository,
        LarpFaction           $faction,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            if (!$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $faction, 'Faction')) {
                return $this->redirectToRoute('backoffice_larp_story_faction_list', [
                    'larp' => $larp->getId(),
                ]);
            }
        }

        $factionRepository->remove($faction);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_faction_list', [
            'larp' => $larp->getId(),
        ]);
    }
    
    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}
