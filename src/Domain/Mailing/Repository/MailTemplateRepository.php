<?php

namespace App\Domain\Mailing\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Entity\MailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailTemplate>
 */
class MailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTemplate::class);
    }

    public function save(MailTemplate $template, bool $flush = false): void
    {
        $this->_em->persist($template);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(MailTemplate $template, bool $flush = false): void
    {
        $this->_em->remove($template);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function findOneByLarpAndType(Larp $larp, MailTemplateType $type): ?MailTemplate
    {
        return $this->findOneBy([
            'larp' => $larp,
            'type' => $type,
        ]);
    }
}
