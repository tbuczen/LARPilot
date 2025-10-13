<?php

namespace App\Domain\Core\Repository;

use App\Domain\Core\Entity\Larp;
use Doctrine\ORM\QueryBuilder;

interface ListableRepositoryInterface
{
    public function decorateLarpListQueryBuilder(QueryBuilder $qb, Larp $larp): QueryBuilder;
}
