<?php

namespace App\Domain\EventPlanning\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\PlanningResource;
use App\Domain\EventPlanning\Form\Filter\PlanningResourceFilterType;
use App\Domain\EventPlanning\Form\PlanningResourceType;
use App\Domain\EventPlanning\Repository\PlanningResourceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/event-planner/resource', name: 'backoffice_event_planner_resource_')]
class ResourceController extends BaseController
{
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request, Larp $larp, PlanningResourceRepository $repository): Response
    {
        $filterForm = $this->createForm(PlanningResourceFilterType::class);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('r')
            ->where('r.larp = :larp')
            ->setParameter('larp', $larp);

        // Apply filters using FilterBuilderUpdater
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Sorting
        $sort = $request->query->get('sort', 'type');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('r.' . $sort, $dir);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('backoffice/event_planner/resource/list.html.twig', [
            'larp' => $larp,
            'resources' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/{resource}', name: 'modify', defaults: ['resource' => null], methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        Larp $larp,
        PlanningResourceRepository $repository,
        ?PlanningResource $resource = null
    ): Response {
        $isNew = !($resource instanceof PlanningResource);

        if ($isNew) {
            $resource = new PlanningResource();
            $resource->setLarp($larp);
            $resource->setCreatedBy($this->getUser());
        }

        $form = $this->createForm(PlanningResourceType::class, $resource, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($resource);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_event_planner_resource_list', [
                'larp' => $larp->getId(),
            ]);
        }

        return $this->render('backoffice/event_planner/resource/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'resource' => $resource,
            'isNew' => $isNew,
        ]);
    }

    #[Route('/{resource}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Larp $larp,
        PlanningResourceRepository $repository,
        PlanningResource $resource
    ): Response {
        $repository->remove($resource);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_event_planner_resource_list', [
            'larp' => $larp->getId(),
        ]);
    }
}
