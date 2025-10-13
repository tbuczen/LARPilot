<?php

namespace App\Domain\Incidents\Repository;

use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Incidents\Entity\LarpIncident;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<LarpIncident>
 */
class LarpIncidentRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpIncident::class);
    }
}
