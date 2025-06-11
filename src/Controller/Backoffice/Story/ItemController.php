<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Item;
use App\Form\Filter\ItemFilterType;
use App\Form\ItemType;
use App\Helper\Logger;
use App\Repository\StoryObject\ItemRepository;
use Money\Currency;
use Money\Money;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/item/', name: 'backoffice_larp_story_item_')]
class ItemController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, ItemRepository $repository): Response
    {
        $filterForm = $this->createForm(ItemFilterType::class);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')->setParameter('larp', $larp);

        return $this->render('backoffice/larp/item/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'items' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{item}', name: 'modify', defaults: ['item' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager $larpManager,
        IntegrationManager $integrationManager,
        Request $request,
        Larp $larp,
        ItemRepository $itemRepository,
        ?Item $item = null,
    ): Response {
        $new = false;
        if (!$item) {
            $item = new Item();
            $item->setLarp($larp);
            $item->setCost(new Money(0, new Currency('USD')));
            $new = true;
        }

        $form = $this->createForm(ItemType::class, $item, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $itemRepository->save($item);
            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $item);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_item_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/item/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'item' => $item,
        ]);
    }

    #[Route('{item}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager $larpManager,
        IntegrationManager $integrationManager,
        Larp $larp,
        Request $request,
        ItemRepository $itemRepository,
        Item $item,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');
        if ($deleteIntegrations) {
            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    $integrationService->removeStoryObject($integration, $item);
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. Item not deleted.');
                    return $this->redirectToRoute('backoffice_larp_story_item_list', [ 'larp' => $larp->getId() ]);
                }
            }
        }

        $itemRepository->remove($item);
        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));
        return $this->redirectToRoute('backoffice_larp_story_item_list', ['larp' => $larp->getId()]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}
