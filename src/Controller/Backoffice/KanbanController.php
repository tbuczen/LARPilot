<?php

namespace App\Controller\Backoffice;

use App\Controller\BaseController;
use App\Entity\Enum\KanbanStatus;
use App\Entity\KanbanTask;
use App\Entity\Larp;
use App\Form\KanbanTaskType;
use App\Repository\KanbanTaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/kanban', name: 'backoffice_larp_kanban_')]
class KanbanController extends BaseController
{
    #[Route('', name: 'board', methods: ['GET', 'POST'])]
    public function board(Request $request, Larp $larp, KanbanTaskRepository $repository): Response
    {
        $task = new KanbanTask();
        $form = $this->createForm(KanbanTaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $task->setLarp($larp);
            $repository->save($task);
            return $this->redirectToRoute('backoffice_larp_kanban_board', ['larp' => $larp->getId()]);
        }

        $tasks = $repository->findBy(['larp' => $larp], ['position' => 'ASC']);

        return $this->render('backoffice/larp/kanban/board.html.twig', [
            'larp' => $larp,
            'form' => $form->createView(),
            'tasks' => $tasks,
        ]);
    }

    #[Route('/task/{id}/update', name: 'task_update', methods: ['POST'])]
    public function updateTask(Request $request, KanbanTask $task, KanbanTaskRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['status'])) {
            $task->setStatus(KanbanStatus::from($data['status']));
        }
        if (isset($data['position'])) {
            $task->setPosition((int)$data['position']);
        }
        $repository->save($task);
        return new JsonResponse(['status' => 'ok']);
    }
}
