<?php

namespace App\Repository;

use App\Entity\LarpIntegration;
use App\Entity\SharedFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SharedFile>
 *
 * @method null|SharedFile find($id, $lockMode = null, $lockVersion = null)
 * @method null|SharedFile findOneBy(array $criteria, array $orderBy = null)
 * @method SharedFile[]    findAll()
 * @method SharedFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SharedFileRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SharedFile::class);
    }

    public function existsForIntegration(LarpIntegration $integration, string $fileId): bool
    {
        return $this->createQueryBuilder('sf')
                ->select('1')
                ->where('sf.integration = :integration')
                ->andWhere('sf.fileId = :fileId')
                ->setParameter('integration', $integration)
                ->setParameter('fileId', $fileId)
                ->getQuery()
                ->getOneOrNullResult() !== null;
    }
}
