<?php

namespace App\Domain\StoryObject\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\StoryObject\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Domain\StoryObject\Entity\Item>
 *
 * @method null|\App\Domain\StoryObject\Entity\Item find($id, $lockMode = null, $lockVersion = null)
 * @method null|Item findOneBy(array $criteria, array $orderBy = null)
 * @method \App\Domain\StoryObject\Entity\Item[]    findAll()
 * @method \App\Domain\StoryObject\Entity\Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }
}
