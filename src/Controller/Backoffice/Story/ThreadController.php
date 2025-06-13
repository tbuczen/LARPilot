<?php

namespace App\Controller\Backoffice\Story;

use App\Controller\BaseController;
use App\Entity\Larp;
use App\Entity\StoryObject\Thread;
use App\Form\Filter\ThreadFilterType;
use App\Form\ThreadType;
use App\Helper\Logger;
use App\Repository\StoryObject\ThreadRepository;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
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
        LarpManager        $larpManager,
        IntegrationManager $integrationManager,
        Request            $request,
        Larp               $larp,
        ThreadRepository   $threadRepository,
        ?Thread            $thread = null,
    ): Response
    {

        $new = false;
        if (!$thread) {
            $thread = new Thread();
            $thread->setLarp($larp);
            $new = true;
        }

        $form = $this->createForm(ThreadType::class, $thread, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $threadRepository->save($thread);

            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    if ($new) {
                        $integrationService->createStoryObject($integration, $thread);
                    } else {
                        $integrationService->syncStoryObject($integration, $thread);
                    }
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('warning', 'Failed to sync with ' . $integration->getProvider()->name);
                }
            }

            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
            return $this->redirectToRoute('backoffice_larp_story_thread_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/thread/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'thread' => $thread,
        ]);
    }

    #[Route('{thread}/tree', name: 'tree', methods: ['GET', 'POST'])]
    public function tree(
        Request         $request,
        Larp            $larp,
        Thread          $thread,
        ThreadRepository $threadRepository,
    ): Response {
        if ($request->isMethod('POST')) {
            $treeData = $request->request->get('decisionTree', '[]');
            $thread->setDecisionTree(json_decode($treeData, true) ?? []);
            $threadRepository->save($thread);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));
        }

        return $this->render('backoffice/larp/thread/tree.html.twig', [
            'larp' => $larp,
            'thread' => $thread,
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
    ): Response
    {
        $deleteIntegrations = $request->query->getBoolean('integrations');

        if ($deleteIntegrations) {
            $integrations = $larpManager->getIntegrationsForLarp($larp);
            foreach ($integrations as $integration) {
                try {
                    $integrationService = $integrationManager->getService($integration);
                    $integrationService->removeStoryObject($integration, $thread);
                } catch (\Throwable $e) {
                    Logger::get()->error($e->getMessage(), $e->getTrace());
                    $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. Thread not deleted.');
                    return $this->redirectToRoute('backoffice_larp_story_thread_list', [
                        'larp' => $larp->getId(),
                    ]);
                }
            }
        }

        $threadRepository->remove($thread);

        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_thread_list', [
            'larp' => $larp->getId(),
        ]);
    }

    #[Route('import/file', name: 'import_file', methods: ['GET', 'POST'])]
    public function importFile(Larp $larp, LarpManager $larpManager): Response
    {
        return new Response('TODO:: Import from file csv/xlsx');
    }
}