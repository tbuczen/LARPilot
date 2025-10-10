<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Enum\KanbanStatus;
use App\Entity\Enum\TaskVisibility;
use App\Entity\KanbanTask;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Form\KanbanTaskType;
use App\Repository\KanbanTaskRepository;
use App\Repository\LarpParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/kanban', name: 'backoffice_larp_kanban_')]
class KanbanController extends BaseController
{
    #[Route('', name: 'board', methods: ['GET'])]
    public function board(Larp $larp, KanbanTaskRepository $repository): Response
    {
        $tasks = $repository->findBy(['larp' => $larp], ['position' => 'ASC']);
        $this->entityPreloader->preload($tasks, 'assignedTo');
        return $this->render('backoffice/larp/kanban/board.html.twig', [
            'larp' => $larp,
            'tasks' => $tasks,
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
                'assignedTo' => $task->getAssignedTo() instanceof LarpParticipant ? [
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
