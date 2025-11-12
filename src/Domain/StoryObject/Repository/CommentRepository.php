<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Repository;

use App\Domain\StoryObject\Entity\Comment;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Find all top-level comments for a story object (no parent), ordered by creation date
     *
     * @return Comment[]
     */
    public function findTopLevelByStoryObject(StoryObject $storyObject): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.storyObject = :storyObject')
            ->andWhere('c.parent IS NULL')
            ->setParameter('storyObject', $storyObject)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all comments for a story object (including nested), ordered by creation date
     *
     * @return Comment[]
     */
    public function findByStoryObject(StoryObject $storyObject): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.storyObject = :storyObject')
            ->setParameter('storyObject', $storyObject)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find replies to a specific comment
     *
     * @return Comment[]
     */
    public function findReplies(Comment $parent): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStoryObject(StoryObject $storyObject): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.storyObject = :storyObject')
            ->setParameter('storyObject', $storyObject)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count unresolved comments for a story object
     */
    public function countUnresolvedByStoryObject(StoryObject $storyObject): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.storyObject = :storyObject')
            ->andWhere('c.isResolved = false')
            ->setParameter('storyObject', $storyObject)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Comment $comment, bool $flush = false): void
    {
        $this->getEntityManager()->persist($comment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Comment $comment, bool $flush = false): void
    {
        $this->getEntityManager()->remove($comment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
