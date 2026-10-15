<?php

namespace App\Domain\Integrations\Repository;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Repository\BaseRepository;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends BaseRepository<LarpIntegration>
 *
 * @method null|LarpIntegration find($id, $lockMode = null, $lockVersion = null)
 * @method null|LarpIntegration findOneBy(array $criteria, array $orderBy = null)
 * @method LarpIntegration[]    findAll()
 * @method LarpIntegration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LarpIntegrationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LarpIntegration::class);
    }

    /**
     * @return LarpIntegration[]
     */
    public function findAllByLarp(string|Larp $larp): array
    {
        return $this->createQueryBuilder('li')
            ->andWhere('li.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('li.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByLarpAndProvider(string $larpId, LarpIntegrationProvider $provider): LarpIntegration
    {
        return $this->findOneBy([
            'larp' => $larpId,
            'provider' => $provider,
        ]);
    }

    /**
     * @return array{accessToken: ?string, refreshToken: ?string, expiresAt: ?\DateTimeInterface}|null
     */
    public function findStoredToken(Uuid $integrationId): ?array
    {
        $storedToken = $this->createQueryBuilder('li')
            ->select('li.accessToken', 'li.refreshToken', 'li.expiresAt')
            ->andWhere('li.id = :integrationId')
            ->setParameter('integrationId', $integrationId, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($storedToken)) {
            return null;
        }

        return [
            'accessToken' => $storedToken['accessToken'],
            'refreshToken' => $storedToken['refreshToken'],
            'expiresAt' => $storedToken['expiresAt'],
        ];
    }

    public function countOtherInstallsOfExternalAccount(LarpIntegration $integration, string $externalAccountId): int
    {
        return (int) $this->createQueryBuilder('li')
            ->select('COUNT(li.id)')
            ->andWhere('li.provider = :provider')
            ->andWhere('li.externalAccountId = :externalAccountId')
            ->andWhere('li.id != :integrationId')
            ->setParameter('provider', $integration->getProvider())
            ->setParameter('externalAccountId', $externalAccountId)
            ->setParameter('integrationId', $integration->getId(), UuidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByLarpAndProvider(Larp $larp, LarpIntegrationProvider $provider): ?LarpIntegration
    {
        return $this->findOneBy(['larp' => $larp, 'provider' => $provider]);
    }
}
