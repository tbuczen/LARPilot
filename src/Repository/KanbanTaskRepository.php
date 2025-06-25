<?php

namespace App\Repository;

use App\Entity\KanbanTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KanbanTask>
 */
class KanbanTaskRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KanbanTask::class);
    }

    /**
     * @param KanbanTask[] $tasks
     */
    public function saveMany(array $tasks): void
    {
        foreach ($tasks as $task) {
            $this->getEntityManager()->persist($task);
        }
        $this->getEntityManager()->flush();
    }
}
