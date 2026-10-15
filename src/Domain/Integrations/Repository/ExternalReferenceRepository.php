<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalItem;
use App\Domain\Integrations\Entity\ExternalReference;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<ExternalReference>
 *
 * @method null|ExternalReference find($id, $lockMode = null, $lockVersion = null)
 * @method null|ExternalReference findOneBy(array $criteria, array $orderBy = null)
 * @method ExternalReference[]    findAll()
 * @method ExternalReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExternalReferenceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalReference::class);
    }

    /**
     * @return ExternalReference[]
     */
    public function findForStoryObject(StoryObject $storyObject): array
    {
        return $this->createQueryBuilder('reference')
            ->andWhere('reference.storyObject = :storyObject')
            ->setParameter('storyObject', $storyObject)
            ->orderBy('reference.createdAt', 'DESC')
            ->addOrderBy('reference.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ExternalReference[]
     */
    public function findForLarp(Larp $larp, ?LarpIntegrationProvider $provider = null): array
    {
        $queryBuilder = $this->createQueryBuilder('reference')
            ->addSelect('storyObject')
            ->leftJoin('reference.storyObject', 'storyObject')
            ->andWhere('reference.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('reference.createdAt', 'DESC')
            ->addOrderBy('reference.id', 'ASC');

        if ($provider !== null) {
            $queryBuilder
                ->andWhere('reference.provider = :provider')
                ->setParameter('provider', $provider);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneForStoryObject(
        StoryObject $storyObject,
        LarpIntegrationProvider $provider,
        string $externalId,
    ): ?ExternalReference {
        return $this->findOneBy([
            'storyObject' => $storyObject,
            'provider' => $provider,
            'externalId' => $externalId,
        ]);
    }

    /**
     * @param list<string> $externalIds
     * @return ExternalReference[]
     */
    public function findForSourceOrExternalIds(ExternalSource $source, array $externalIds): array
    {
        $integration = $source->getIntegration();
        $queryBuilder = $this->createQueryBuilder('reference')
            ->addSelect('storyObject')
            ->leftJoin('reference.storyObject', 'storyObject')
            ->andWhere('reference.larp = :larp')
            ->setParameter('larp', $integration->getLarp())
            ->setParameter('source', $source)
            ->orderBy('reference.createdAt', 'ASC')
            ->addOrderBy('reference.id', 'ASC');

        if ($externalIds === []) {
            return $queryBuilder->andWhere('reference.source = :source')->getQuery()->getResult();
        }

        return $queryBuilder
            ->andWhere('reference.source = :source OR (reference.provider = :provider AND reference.externalId IN (:externalIds))')
            ->setParameter('provider', $integration->getProvider())
            ->setParameter('externalIds', $externalIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, list<string|null>>
     */
    public function findLinkedNamesByExternalItem(ExternalSource $source): array
    {
        $rows = $this->createQueryBuilder('reference')
            ->select('IDENTITY(reference.externalItem) AS externalItemId', 'reference.name AS name')
            ->andWhere('reference.source = :source')
            ->andWhere('reference.externalItem IS NOT NULL')
            ->setParameter('source', $source)
            ->getQuery()
            ->getArrayResult();

        $namesByExternalItem = [];
        foreach ($rows as $row) {
            $namesByExternalItem[(string) $row['externalItemId']][] = $row['name'];
        }

        return $namesByExternalItem;
    }

    public function countForExternalItem(ExternalItem $externalItem): int
    {
        return $this->count(['externalItem' => $externalItem]);
    }

    public function clearExcerptsForLarp(Larp $larp, LarpIntegrationProvider $provider): int
    {
        return (int) $this->createQueryBuilder('reference')
            ->update()
            ->set('reference.excerpt', ':emptyExcerpt')
            ->andWhere('reference.larp = :larp')
            ->andWhere('reference.provider = :provider')
            ->setParameter('emptyExcerpt', null)
            ->setParameter('larp', $larp)
            ->setParameter('provider', $provider)
            ->getQuery()
            ->execute();
    }
}
