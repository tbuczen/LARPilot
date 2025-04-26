<?php

namespace App\Repository\StoryObject;

use App\Entity\StoryObject\Quest;
use App\Repository\BaseRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quest>
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
