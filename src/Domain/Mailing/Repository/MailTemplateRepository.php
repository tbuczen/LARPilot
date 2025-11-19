<?php

namespace App\Domain\Mailing\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Mailing\Entity\Enum\MailTemplateType;
use App\Domain\Mailing\Entity\MailTemplate;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<MailTemplate>
 */
class MailTemplateRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTemplate::class);
    }

    public function findOneByLarpAndType(Larp $larp, MailTemplateType $type): ?MailTemplate
    {
        return $this->findOneBy([
            'larp' => $larp,
            'type' => $type,
        ]);
    }
}
