<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Quest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\StoryObject\Entity\Quest>
 *
 * @method null|Quest find($id, $lockMode = null, $lockVersion = null)
 * @method null|Quest findOneBy(array $criteria, array $orderBy = null)
 * @method Quest[]    findAll()
 * @method Quest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quest::class);
    }
}
