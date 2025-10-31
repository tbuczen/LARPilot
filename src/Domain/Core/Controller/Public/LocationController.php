<?php

namespace App\Domain\Core\Controller\Public;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Repository\LocationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'public_location_')]
class LocationController extends BaseController
{
    #[Route('/locations', name: 'list', methods: ['GET'])]
    public function list(Request $request, LocationRepository $locationRepository): Response
    {
        $qb = $locationRepository->createQueryBuilder('l')
            ->where('l.isPublic = :isPublic')
            ->andWhere('l.isActive = :isActive')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->orderBy('l.title', 'ASC');

        $sortBy = $request->query->get('sortBy', 'title');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        switch ($sortBy) {
            case 'city':
                $qb->orderBy('l.city', $sortOrder);
                break;
            case 'country':
                $qb->orderBy('l.country', $sortOrder);
                break;
            case 'title':
            default:
                $qb->orderBy('l.title', $sortOrder);
                break;
        }

        $pagination = $this->getPagination($qb, $request);

        return $this->render('public/location/list.html.twig', [
            'locations' => $pagination,
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

        // Get LARPs at this location (sorted by start date, most recent first)
        $larps = $location->getLarps()
            ->filter(fn ($larp) => $larp->getStatus()->isVisibleForEveryone())
            ->toArray();

        usort($larps, fn ($a, $b) => $b->getStartDate() <=> $a->getStartDate());

        return $this->render('public/location/details.html.twig', [
            'location' => $location,
            'larps' => $larps,
        ]);
    }
}
