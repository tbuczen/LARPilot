<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\LarpManager;
use App\Domain\Integrations\Service\IntegrationManager;
use App\Domain\StoryMarketplace\Entity\Enum\RecruitmentProposalStatus;
use App\Domain\StoryMarketplace\Entity\RecruitmentProposal;
use App\Domain\StoryMarketplace\Entity\StoryRecruitment;
use App\Domain\StoryMarketplace\Form\RecruitmentProposalType;
use App\Domain\StoryMarketplace\Form\StoryRecruitmentType;
use App\Domain\StoryMarketplace\Repository\RecruitmentProposalRepository;
use App\Domain\StoryMarketplace\Repository\StoryRecruitmentRepository;
use App\Domain\StoryObject\Entity\Thread;
use App\Domain\StoryObject\Form\Filter\ThreadFilterType;
use App\Domain\StoryObject\Form\ThreadType;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\ThreadRepository;
use App\Domain\StoryObject\Service\StoryObjectMentionService;
use App\Domain\StoryObject\Service\StoryObjectTextLinker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/thread/', name: 'backoffice_larp_story_thread_')]
class ThreadController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function threads(Request $request, Larp $larp, ThreadRepository $repository): Response
    {
        $filterForm = $this->createForm(ThreadFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('c');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');

        $qb->orderBy('c.' . $sort, $dir);
        $qb->andWhere('c.larp = :larp')
            ->setParameter('larp', $larp);

        return $this->render('backoffice/larp/thread/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'threads' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{thread}', name: 'modify', defaults: ['thread' => null], methods: ['GET', 'POST'])]
    public function modify(
        LarpManager                                           $larpManager,
        IntegrationManager                                    $integrationManager,
        StoryObjectMentionService                             $mentionService,
        Request                                               $request,
        Larp                                                  $larp,
        ThreadRepository                                      $threadRepository,
        CharacterRepository                                   $characterRepository,
        StoryObjectTextLinker $textLinker,
        ?Thread                                               $thread = null,
    ): Response {
        $new = false;
        if (!$thread instanceof Thread) {
            $thread = new Thread();
            $thread->setLarp($larp);
            $new = true;
            
            // Pre-fill with character from query parameter
            $characterId = $request->query->get('character');
            if ($characterId) {
                $character = $characterRepository->find($characterId);
                if ($character && $character->getLarp() === $larp) {
                    $thread->addInvolvedCharacter($character);
                }
            }
        }

        $form = $this->createForm(ThreadType::class, $thread, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $thread->setDescription(
                $textLinker->finalizeMentions((string) $thread->getDescription(), $larp)
            );
            $threadRepository->save($thread);

            $this->processIntegrationsForStoryObject($larpManager, $larp, $integrationManager, $new, $thread);

            $this->addFlash('success', $this->translator->trans('success_save'));
            return $this->redirectToRoute('backoffice_larp_story_thread_list', ['larp' => $larp->getId()]);
        }

        // Get mentions only for existing threads (not new ones)
        $mentions = [];
        if (!$new) {
            $mentions = $mentionService->findMentions($thread);
        }

        return $this->render('backoffice/larp/thread/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'thread' => $thread,
            'mentions' => $mentions,
        ]);
    }

    #[Route('{thread}/tree', name: 'tree', methods: ['GET', 'POST'])]
    public function tree(
        Request                   $request,
        Larp                      $larp,
        Thread                    $thread,
        ThreadRepository          $threadRepository,
        StoryObjectMentionService $mentionService,
    ): Response {
        if ($request->isMethod('POST')) {
            $treeData = $request->request->get('decisionTree', '[]');
            $thread->setDecisionTree(json_decode($treeData, true) ?? []);
            $threadRepository->save($thread);
            $this->addFlash('success', $this->translator->trans('success_save'));
        }

        $mentions = $mentionService->findMentions($thread);

        return $this->render('backoffice/larp/thread/tree.html.twig', [
            'larp' => $larp,
            'thread' => $thread,
            'mentions' => $mentions,
        ]);
    }

    #[Route('{thread}/mentions', name: 'mentions', methods: ['GET'])]
    public function mentions(
        Larp                      $larp,
        Thread                    $thread,
        StoryObjectMentionService $mentionService,
    ): Response {
        $mentions = $mentionService->findMentions($thread);

        return $this->render('backoffice/larp/thread/mentions.html.twig', [
            'larp' => $larp,
            'thread' => $thread,
            'mentions' => $mentions,
        ]);
    }

    #[Route('{thread}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        LarpManager        $larpManager,
        IntegrationManager $integrationManager,
        Larp               $larp,
        Request            $request,
        ThreadRepository   $threadRepository,
        Thread             $thread,
    ): Response {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations && !$this->removeStoryObjectFromIntegrations($larpManager, $larp, $integrationManager, $thread, 'Thread')) {
            return $this->redirectToRoute('backoffice_larp_story_thread_list', [
                'larp' => $larp->getId(),
            ]);
        }

        $threadRepository->remove($thread);

        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_thread_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }

    #[Route('recruitments', name: 'recruitment_list', methods: ['GET'])]
    public function recruitmentList(Larp $larp, StoryRecruitmentRepository $recruitmentRepository): Response
    {
        $recruitments = $recruitmentRepository->createQueryBuilder('r')
            ->join('r.storyObject', 'o')
            ->andWhere('o INSTANCE OF ' . Thread::class)
            ->andWhere('o.larp = :larp')
            ->setParameter('larp', $larp)
            ->getQuery()
            ->getResult();

        return $this->render('backoffice/larp/recruitment/list.html.twig', [
            'recruitments' => $recruitments,
            'larp' => $larp,
            'modify_route' => 'backoffice_larp_story_thread_recruitment',
            'proposal_route' => 'backoffice_larp_story_thread_proposal',
        ]);
    }

    #[Route('recruitment/{recruitment}/proposals', name: 'proposal_list', methods: ['GET'])]
    public function proposalList(StoryRecruitment $recruitment): Response
    {
        return $this->render('backoffice/larp/proposal/list.html.twig', [
            'proposals' => $recruitment->getProposals(),
            'larp' => $recruitment->getStoryObject()->getLarp(),
            'accept_route' => 'backoffice_larp_story_thread_proposal_accept',
            'reject_route' => 'backoffice_larp_story_thread_proposal_reject',
            'create_route' => 'backoffice_larp_story_thread_proposal',
            'recruitment' => $recruitment,
        ]);
    }

    #[Route('recruitment/{recruitment}/proposal', name: 'proposal', methods: ['GET', 'POST'])]
    public function proposal(
        Request                      $request,
        Larp                         $larp,
        StoryRecruitment             $recruitment,
        RecruitmentProposalRepository $proposalRepository,
    ): Response {
        $proposal = new RecruitmentProposal();
        $proposal->setRecruitment($recruitment);

        $form = $this->createForm(RecruitmentProposalType::class, $proposal, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proposalRepository->save($proposal);

            return $this->redirectToRoute('backoffice_larp_story_thread_proposal_list', [
                'larp' => $larp->getId(),
                'recruitment' => $recruitment->getId(),
            ]);
        }

        return $this->render('backoffice/larp/proposal/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('{thread}/recruitment', name: 'recruitment', defaults: ['recruitment' => null], methods: ['GET', 'POST'])]
    public function recruitment(
        Request                    $request,
        Larp                       $larp,
        Thread                     $thread,
        StoryRecruitmentRepository $recruitmentRepository,
        ?StoryRecruitment          $recruitment = null,
    ): Response {
        if (!$recruitment instanceof StoryRecruitment) {
            $recruitment = new StoryRecruitment();
            $recruitment->setStoryObject($thread);
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $recruitment->setCreatedBy($currentUser);
            }
        }

        $form = $this->createForm(StoryRecruitmentType::class, $recruitment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recruitmentRepository->save($recruitment);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_story_thread_list', [
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

        return $this->redirectToRoute('backoffice_larp_story_thread_list', [
            'larp' => $proposal->getRecruitment()->getStoryObject()->getLarp()->getId(),
        ]);
    }

    #[Route('proposal/{proposal}/reject', name: 'proposal_reject', methods: ['POST'])]
    public function rejectProposal(RecruitmentProposal $proposal, RecruitmentProposalRepository $proposalRepository): Response
    {
        $proposal->setStatus(RecruitmentProposalStatus::REJECTED);
        $proposalRepository->save($proposal);

        return $this->redirectToRoute('backoffice_larp_story_thread_list', [
            'larp' => $proposal->getRecruitment()->getStoryObject()->getLarp()->getId(),
        ]);
    }
}
