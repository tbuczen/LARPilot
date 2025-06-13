<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\StoryObject;
use App\Form\Filter\QuestFilterType;
use App\Form\QuestType;
use App\Helper\Logger;
use App\Repository\StoryObject\QuestRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/quest/', name: 'backoffice_larp_story_quest_')]

class QuestController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function quests(Request $request, Larp $larp, QuestRepository $repository): Response
    {
        $filterForm = $this->createForm(QuestFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');

        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);

        return $this->render('backoffice/larp/quest/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'quests' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{quest}', name: 'modify', defaults: ['quest' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Request                 $request,
        Larp                    $larp,
        QuestRepository $questRepository,
        ?Quest          $quest = null,
    ): Response
    {

        $new = false;
        if (!$quest) {
            $quest = new Quest();
            $quest->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(QuestType::class, $quest, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $questRepository->save($quest);
            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $quest);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_quest_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/quest/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('{quest}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager             $larpManager,
        IntegrationManager      $integrationManager,
        Larp                    $larp,
        Request                 $request,
        QuestRepository $questRepository,
        Quest           $quest,
    ): Response
    {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    $integrationService->removeStoryObject($integration, $quest);
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. Quest not deleted.');
                    return $this->redirectToRoute('backoffice_larp_story_quest_list', [
                        'larp' => $larp->getId(),
                    ]);
                }
            }
        }

        $questRepository->remove($quest);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_quest_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}