<?php

namespace App\Domain\Kanban\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Kanban\Entity\KanbanTask;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<KanbanTask>
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
