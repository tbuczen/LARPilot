<?php

namespace App\Domain\Core\Controller\Public;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Form\Filter\LocationPublicFilterType;
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
        // Get distinct cities and countries for filter dropdowns
        $cities = $locationRepository->createQueryBuilder('l')
            ->select('DISTINCT l.city')
            ->where('l.isPublic = :isPublic')
            ->andWhere('l.isActive = :isActive')
            ->andWhere('l.city IS NOT NULL')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->orderBy('l.city', 'ASC')
            ->getQuery()
            ->getResult();

        $countries = $locationRepository->createQueryBuilder('l')
            ->select('DISTINCT l.country')
            ->where('l.isPublic = :isPublic')
            ->andWhere('l.isActive = :isActive')
            ->andWhere('l.country IS NOT NULL')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->orderBy('l.country', 'ASC')
            ->getQuery()
            ->getResult();

        // Transform to choice array format
        $cityChoices = array_combine(
            array_column($cities, 'city'),
            array_column($cities, 'city')
        );
        $countryChoices = array_combine(
            array_column($countries, 'country'),
            array_column($countries, 'country')
        );

        // Create and handle filter form
        $filterForm = $this->createForm(LocationPublicFilterType::class, null, [
            'cities' => $cityChoices,
            'countries' => $countryChoices,
        ]);
        $filterForm->handleRequest($request);

        // Build query
        $qb = $locationRepository->createQueryBuilder('l')
            ->where('l.isPublic = :isPublic')
            ->andWhere('l.isActive = :isActive')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true);

        // Apply filters
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        // Apply manual filters
        $filterData = $filterForm->getData();
        if (!empty($filterData['city'])) {
            $qb->andWhere('l.city = :city')
                ->setParameter('city', $filterData['city']);
        }
        if (!empty($filterData['country'])) {
            $qb->andWhere('l.country = :country')
                ->setParameter('country', $filterData['country']);
        }
        if (!empty($filterData['search'])) {
            $qb->andWhere('LOWER(l.title) LIKE :search OR LOWER(l.description) LIKE :search')
                ->setParameter('search', '%' . strtolower($filterData['search']) . '%');
        }

        // Apply sorting
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

        // Get all filtered locations for map (not paginated)
        $allLocations = (clone $qb)->getQuery()->getResult();

        // Get paginated results
        $pagination = $this->getPagination($qb, $request);

        return $this->render('public/location/list.html.twig', [
            'locations' => $pagination,
            'allLocations' => $allLocations,
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

        $user = $this->getUser();

        // Filter LARPs based on user access
        $larps = $location->getLarps()
            ->filter(function ($larp) use ($user) {
                // Show only published LARPs to everyone (most public status)
                if ($larp->getStatus() === \App\Domain\Core\Entity\Enum\LarpStageStatus::PUBLISHED) {
                    return true;
                }

                // For authenticated users, also show LARPs they're participating in
                if ($user instanceof \App\Domain\Account\Entity\User) {
                    return $larp->getParticipants()->exists(
                        fn ($key, $participant) => $participant->getUser()->getId() === $user->getId()
                    );
                }

                return false;
            })
            ->toArray();

        usort($larps, fn ($a, $b) => $b->getStartDate() <=> $a->getStartDate());

        return $this->render('public/location/details.html.twig', [
            'location' => $location,
            'larps' => $larps,
        ]);
    }
}
