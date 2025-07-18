<?php

namespace App\Controller;

use App\Entity\Larp;
use App\Entity\StoryObject\StoryObject;
use App\Helper\Logger;
use App\Repository\StoryObject\ListableRepositoryInterface;
use App\Service\Integrations\IntegrationManager;
use App\Service\Larp\LarpManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class BaseController extends AbstractController
{
    public static int $defaultPageSize = 25;
    protected EntityPreloader $entityPreloader;

    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly FilterBuilderUpdaterInterface $filterBuilderUpdater,
        protected readonly PaginatorInterface $paginator,
        protected readonly EntityManagerInterface $entityManager,
    ) {
        $this->entityPreloader = new EntityPreloader($entityManager);
    }

    protected function showErrorsAsFlash(FormErrorIterator $errors): void
    {
        /** @var FormError $error */
        foreach ($errors as $error) {
            $fieldName = $error->getOrigin()?->getName();

            if ($fieldName) {
                $errorMessage = $error->getMessage();
                $this->addFlash('error', $fieldName . ': ' . $errorMessage);
            }
        }
    }

    protected function getListQueryBuilder(EntityRepository $repo, FormInterface $filterForm, Request $request, ?Larp $larp = null): QueryBuilder
    {
        $qb = $repo->createQueryBuilder('c');
        if ($larp !== null && $repo instanceof ListableRepositoryInterface) {
            $qb = $repo->decorateLarpListQueryBuilder($qb, $larp);
        }

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('c.' . $sort, $dir);
        return $qb;
    }

    protected function getPagination(QueryBuilder $qb, Request $request): \Knp\Component\Pager\Pagination\PaginationInterface
    {
        return $this->paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            self::$defaultPageSize
        );
    }

    protected function processIntegrationsForStoryObject(
        LarpManager        $larpManager,
        Larp               $larp,
        IntegrationManager $integrationManager,
        bool $new,
        ?StoryObject $storyObject
    ): void {
        $integrations = $larpManager->getIntegrationsForLarp($larp);
        foreach ($integrations as $integration) {
            try {
                $integrationService = $integrationManager->getService($integration);
                if ($new) {
                    $integrationService->createStoryObject($integration, $storyObject);
                } else {
                    $integrationService->syncStoryObject($integration, $storyObject);
                }
            } catch (\Throwable $e) {
                Logger::get()->error($e->getMessage(), $e->getTrace());
                $this->addFlash('warning', 'Failed to sync with ' . $integration->getProvider()->name);
            }
        }
    }

    protected function removeStoryObjectFromIntegrations(
        LarpManager        $larpManager,
        Larp               $larp,
        IntegrationManager $integrationManager,
        StoryObject        $storyObject,
        string             $objectName
    ): bool {
        $integrations = $larpManager->getIntegrationsForLarp($larp);
        foreach ($integrations as $integration) {
            try {
                $integrationService = $integrationManager->getService($integration);
                $integrationService->removeStoryObject($integration, $storyObject);
            } catch (\Throwable $e) {
                Logger::get()->error($e->getMessage(), $e->getTrace());
                $this->addFlash('danger', 'Failed to remove from ' . $integration->getProvider()->name . '. ' . $objectName . ' not deleted.');

                return false;
            }
        }

        return true;
    }
}
