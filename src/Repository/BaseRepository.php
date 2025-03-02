<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class BaseRepository extends ServiceEntityRepository
{

    public function create(object $entity, bool $flush = true): object
    {
        // If the entity has a setCreatedBy method and it's not already set, use the current user.

        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
        return $entity;
    }
}
