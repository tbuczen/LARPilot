<?php

namespace App\Controller\Public;

use App\Controller\BaseController;
use App\Form\Filter\LarpPublicFilterType;
use App\Repository\LarpRepository;
use App\Repository\LocationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'public_location_')]
class LocationController extends BaseController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(Request $request, LarpRepository $larpRepository): Response
    {
        $filterForm = $this->createForm(LarpPublicFilterType::class);
        $filterForm->handleRequest($request);

        $qb = $this->getListQueryBuilder($larpRepository, $filterForm, $request);
        //        $qb = $larpRepository->findAllUpcomingPublished($this->getUser());

        $pagination = $this->getPagination($qb, $request);

        return $this->render('public/larp/list.html.twig', [
            'larps' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/location/{slug}', name: 'details', methods: ['GET'])]
    public function details(
        string $slug,
        LocationRepository $repository,
    ): Response {
        $location = $repository->findOneBy(['slug' => $slug]);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        return $this->render('public/location/details.html.twig', [
            'location' => $location,
        ]);
    }
}
