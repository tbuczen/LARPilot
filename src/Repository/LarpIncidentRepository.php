<?php

namespace App\Repository;

use App\Entity\LarpIncident;
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
