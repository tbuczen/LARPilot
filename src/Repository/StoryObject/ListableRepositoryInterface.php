<?php

namespace App\Repository\StoryObject;

use App\Entity\Larp;
use Doctrine\ORM\QueryBuilder;

interface ListableRepositoryInterface
{
    public function createListQueryBuilder(Larp $larp): QueryBuilder;
}
