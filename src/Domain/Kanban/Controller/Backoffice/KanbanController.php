<?php

namespace App\Domain\Kanban\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Kanban\Entity\Enum\KanbanStatus;
use App\Domain\Kanban\Entity\KanbanTask;
use App\Domain\Kanban\Form\Filter\KanbanTaskFilterType;
use App\Domain\Kanban\Form\KanbanTaskType;
use App\Domain\Kanban\Repository\KanbanTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/kanban', name: 'backoffice_larp_kanban_')]
class KanbanController extends BaseController
{
    #[Route('', name: 'board', methods: ['GET'])]
    public function board(Request $request, Larp $larp, KanbanTaskRepository $repository): Response
    {
        // Create filter form
        $filterForm = $this->createForm(KanbanTaskFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        // Build base query
        $qb = $repository->createQueryBuilder('kt')
            ->where('kt.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('kt.position', 'ASC');

        // Apply filters
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Handle priority filter manually
        if ($filterForm->isSubmitted() && $filterForm->get('priority')->getData()) {
            $priorityFilter = $filterForm->get('priority')->getData();

            switch ($priorityFilter) {
                case 'high':
                    $qb->andWhere('kt.priority >= 7');
                    break;
                case 'medium':
                    $qb->andWhere('kt.priority >= 4 AND kt.priority < 7');
                    break;
                case 'low':
                    $qb->andWhere('kt.priority > 0 AND kt.priority < 4');
                    break;
                case 'none':
                    $qb->andWhere('kt.priority = 0');
                    break;
            }
        }

        $tasks = $qb->getQuery()->getResult();
        $this->entityPreloader->preload($tasks, 'assignedTo');

        return $this->render('backoffice/larp/kanban/board.html.twig', [
            'larp' => $larp,
            'tasks' => $tasks,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/task/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function createTask(Request $request, Larp $larp, KanbanTaskRepository $repository): Response
    {
        $task = new KanbanTask();
        $form = $this->createForm(KanbanTaskType::class, $task, ['larp' => $larp]);
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $task->setLarp($larp);
                $repository->save($task);
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Task created successfully'
                    ]);
                }
                
                return $this->redirectToRoute('backoffice_larp_kanban_board', ['larp' => $larp->getId()]);
            }
            
            // If form has errors and it's an AJAX request, return JSON with HTML
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'html' => $this->renderView('backoffice/larp/kanban/_task_form.html.twig', [
                        'actionUrl' => $this->generateUrl('backoffice_larp_kanban_task_create', ['larp' => $larp->getId()->toRfc4122()]),
                        'form' => $form->createView(),
                        'larp' => $larp,
                    ])
                ]);
            }
        }

        // For GET requests (loading the form), return HTML directly
        return $this->render('backoffice/larp/kanban/_task_form.html.twig', [
            'actionUrl' => $this->generateUrl('backoffice_larp_kanban_task_create', ['larp' => $larp->getId()->toRfc4122()]),
            'form' => $form->createView(),
            'larp' => $larp,
        ]);
    }

    #[Route('/task/{task}', name: 'task_detail', methods: ['GET'])]
    public function taskDetail(KanbanTask $task): Response
    {
        return $this->render('backoffice/larp/kanban/_task_detail.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/task/{task}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function editTask(Request $request, Larp $larp, KanbanTask $task, KanbanTaskRepository $repository): Response
    {
        $form = $this->createForm(KanbanTaskType::class, $task, ['larp' => $task->getLarp()]);
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                $repository->save($task);
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Task updated successfully'
                    ]);
                }
                
                return $this->redirectToRoute('backoffice_larp_kanban_board', ['larp' => $task->getLarp()->getId()]);
            }
            
            // If form has errors and it's an AJAX request, return JSON with HTML
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'html' => $this->renderView('backoffice/larp/kanban/_task_form.html.twig', [
                        'actionUrl' => $this->generateUrl('backoffice_larp_kanban_task_edit', ['task' => $task->getId(), 'larp' => $larp->getId()->toRfc4122()]),
                        'form' => $form->createView(),
                        'task' => $task,
                    ])
                ]);
            }
        }

        // For GET requests (loading the form), return HTML directly
        return $this->render('backoffice/larp/kanban/_task_form.html.twig', [
            'actionUrl' => $this->generateUrl('backoffice_larp_kanban_task_edit', ['task' => $task->getId(), 'larp' => $larp->getId()->toRfc4122()]),
            'form' => $form->createView(),
            'larp' => $larp,
            'task' => $task,
        ]);
    }

    #[Route('/task/{task}/update', name: 'task_update', methods: ['POST'])]
    public function updateTask(
        Request $request,
        KanbanTask $task,
        KanbanTaskRepository $repository,
        LarpParticipantRepository $participantRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['status'])) {
            $task->setStatus(KanbanStatus::from($data['status']));
        }
        if (isset($data['position'])) {
            $task->setPosition((int)$data['position']);
        }
        if (isset($data['assignedTo'])) {
            $participant = $data['assignedTo'] ?
                $participantRepository->find($data['assignedTo']) : null;
            $task->setAssignedTo($participant);
        }

        $repository->save($task);
        return new JsonResponse([
            'status' => 'ok',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus()->value,
                'position' => $task->getPosition(),
                'assignedTo' => $task->getAssignedTo() instanceof \App\Domain\Core\Entity\LarpParticipant ? [
                    'id' => $task->getAssignedTo()->getId(),
                    'name' => $task->getAssignedTo()->getName()
                ] : null
            ]
        ]);
    }

    #[Route('/task/{task}/assign', name: 'task_assign', methods: ['POST'])]
    public function assignTask(
        Request                   $request,
        KanbanTask                $task,
        EntityManagerInterface    $em,
        LarpParticipantRepository $participantRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['participantId'])) {
            $participant = $participantRepository->find($data['participantId']);
            $task->setAssignedTo($participant);
        } else {
            $task->setAssignedTo(null);
        }
        
        $em->flush();
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/task/{task}/delete', name: 'task_delete', methods: ['DELETE'])]
    public function deleteTask(KanbanTask $task, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($task);
        $em->flush();
        
        return new JsonResponse(['status' => 'ok']);
    }
}
