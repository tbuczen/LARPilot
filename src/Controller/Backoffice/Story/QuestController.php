<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Enum\RecruitmentProposalStatus;
use App\Entity\Larp;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\RecruitmentProposal;
use App\Entity\StoryObject\StoryObject;
use App\Entity\StoryObject\StoryRecruitment;
use App\Form\Filter\QuestFilterType;
use App\Form\QuestType;
use App\Form\StoryRecruitmentType;
use App\Helper\Logger;
use App\Repository\StoryObject\QuestRepository;
use App\Repository\StoryObject\RecruitmentProposalRepository;
use App\Repository\StoryObject\StoryRecruitmentRepository;
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
    ): Response {
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
            'quest' => $quest,
        ]);
    }

    #[Route('{quest}/tree', name: 'tree', methods: ['GET', 'POST'])]
    public function tree(
        Request         $request,
        Larp            $larp,
        Quest           $quest,
        QuestRepository $questRepository,
    ): Response {
        if ($request->isMethod('POST')) {
            $treeData = $request->request->get('decisionTree', '[]');
            $quest->setDecisionTree(json_decode($treeData, true) ?? []);
            $questRepository->save($quest);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
        }

        return $this->render('backoffice/larp/quest/tree.html.twig', [
            'larp' => $larp,
            'quest' => $quest,
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
    ): Response {
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

    #[Route('{quest}/recruitment', name: 'recruitment', defaults: ['recruitment' => null], methods: ['GET', 'POST'])]
    public function recruitment(
        Request                    $request,
        Larp                       $larp,
        Quest                      $quest,
        StoryRecruitmentRepository $recruitmentRepository,
        ?StoryRecruitment          $recruitment = null,
    ): Response {
        if (!$recruitment) {
            $recruitment = new StoryRecruitment();
            $recruitment->setStoryObject($quest);
            $recruitment->setCreatedBy($this->getUser());
        }

        $form = $this->createForm(StoryRecruitmentType::class, $recruitment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recruitmentRepository->save($recruitment);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));

            return $this->redirectToRoute('backoffice_larp_story_quest_list', [
                'larp' => $larp->getId(),
            ]);
        }

        return $this->render('backoffice/larp/recruitment/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('proposal/{proposal}/accept', name: 'proposal_accept', methods: ['POST'])]
    public function acceptProposal(RecruitmentProposal $proposal, RecruitmentProposalRepository $proposalRepository): Response
    {
        $proposal->setStatus(RecruitmentProposalStatus::ACCEPTED);
        $proposalRepository->save($proposal);

        return $this->redirectToRoute('backoffice_larp_story_quest_list', [
            'larp' => $proposal->getRecruitment()->getStoryObject()->getLarp()->getId(),
        ]);
    }

    #[Route('proposal/{proposal}/reject', name: 'proposal_reject', methods: ['POST'])]
    public function rejectProposal(RecruitmentProposal $proposal, RecruitmentProposalRepository $proposalRepository): Response
    {
        $proposal->setStatus(RecruitmentProposalStatus::REJECTED);
        $proposalRepository->save($proposal);

        return $this->redirectToRoute('backoffice_larp_story_quest_list', [
            'larp' => $proposal->getRecruitment()->getStoryObject()->getLarp()->getId(),
        ]);
    }
}
