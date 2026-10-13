<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\SharedFile;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SharedFile>
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
}
