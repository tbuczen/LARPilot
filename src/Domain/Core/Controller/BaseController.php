<?php

namespace App\Domain\Core\Controller;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\ListableRepositoryInterface;
use App\Domain\Core\Service\Helper\Logger;
use App\Domain\Core\Service\LarpManager;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Service\Capability\FileSourceIntegrationInterface;
use App\Domain\Integrations\Service\Exceptions\UnsupportedCapability;
use App\Domain\Integrations\Service\IntegrationRegistry;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use ShipMonk\DoctrineEntityPreloader\EntityPreloader;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        if ($larp instanceof Larp && $repo instanceof ListableRepositoryInterface) {
            $qb = $repo->decorateLarpListQueryBuilder($qb, $larp);
        }

        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('c.' . $sort, $dir);
        return $qb;
    }

    protected function getPagination(QueryBuilder $qb, Request $request): PaginationInterface
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
        IntegrationRegistry $integrationRegistry,
        bool $new,
        ?StoryObject $storyObject
    ): void {
        if (!$new || !$storyObject instanceof StoryObject) {
            return;
        }

        $integrations = $larpManager->getIntegrationsForLarp($larp);
        foreach ($integrations as $integration) {
            if (!$this->canPublishToIntegration($integration, $storyObject)) {
                continue;
            }

            try {
                $integrationService = $integrationRegistry->capability($integration, FileSourceIntegrationInterface::class);
            } catch (UnsupportedCapability) {
                continue;
            }

            try {
                $integrationService->createStoryObject($integration, $storyObject);
            } catch (\Throwable $e) {
                Logger::get()->error($e->getMessage(), $e->getTrace());
                $this->addFlash('warning', 'Failed to sync with ' . $integration->getProvider()->name);
            }
        }
    }

    private function canPublishToIntegration(LarpIntegration $integration, StoryObject $storyObject): bool
    {
        $targetType = $storyObject::getTargetType();
        foreach ($integration->getSharedFiles() as $sharedFile) {
            foreach ($sharedFile->getMappings() as $mapping) {
                if ($mapping->getFileType()->matchesTargetType($targetType)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function removeStoryObjectFromIntegrations(
        LarpManager        $larpManager,
        Larp               $larp,
        IntegrationRegistry $integrationRegistry,
        StoryObject        $storyObject,
        string             $objectName
    ): bool {
        $integrations = $larpManager->getIntegrationsForLarp($larp);
        foreach ($integrations as $integration) {
            if (!$integrationRegistry->supports($integration->getProvider(), FileSourceIntegrationInterface::class)) {
                continue;
            }

            try {
                $integrationService = $integrationRegistry->capability($integration, FileSourceIntegrationInterface::class);
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
