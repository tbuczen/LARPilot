<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Repository\LarpRepository;
use App\Tests\Traits\AuthenticationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests for LarpRepository
 *
 * Tests repository queries for LARP filtering and access control
 */
class LarpRepositoryTest extends KernelTestCase
{
    use AuthenticationTestTrait;

    private ?LarpRepository $larpRepository = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->clearTestData();

        $this->larpRepository = static::getContainer()->get(LarpRepository::class);
    }

    protected function tearDown(): void
    {
        $this->clearTestData();
        parent::tearDown();
    }

    public function test_find_all_returns_all_larps(): void
    {
        $organizer1 = $this->createApprovedUser('organizer1@example.com');
        $organizer2 = $this->createApprovedUser('organizer2@example.com');

        $larp1 = $this->createDraftLarp($organizer1, 'LARP 1');
        $larp2 = $this->createPublishedLarp($organizer2, 'LARP 2');

        $allLarps = $this->larpRepository->findAll();

        $this->assertGreaterThanOrEqual(2, count($allLarps), 'Should find at least 2 LARPs');
    }

    public function test_find_by_user_returns_only_participating_larps(): void
    {
        $organizer1 = $this->createApprovedUser('organizer1@example.com');
        $organizer2 = $this->createApprovedUser('organizer2@example.com');

        $larp1 = $this->createDraftLarp($organizer1, 'LARP 1');
        $larp2 = $this->createDraftLarp($organizer2, 'LARP 2');

        // Find LARPs for organizer1
        $userLarps = $this->larpRepository->createQueryBuilder('l')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->setParameter('user', $organizer1)
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $userLarps, 'User should only see their participating LARPs');
        $this->assertEquals($larp1->getId(), $userLarps[0]->getId());
    }

    public function test_find_publicly_visible_larps(): void
    {
        $organizer = $this->createApprovedUser();

        $draftLarp = $this->createDraftLarp($organizer, 'Draft');
        $wipLarp = $this->createWipLarp($organizer, 'WIP');
        $publishedLarp = $this->createPublishedLarp($organizer, 'Published');
        $inquiriesLarp = $this->createLarp($organizer, LarpStageStatus::INQUIRIES, 'Inquiries');

        // Query for public LARPs
        $publicStatuses = [
            LarpStageStatus::PUBLISHED->value,
            LarpStageStatus::INQUIRIES->value,
            LarpStageStatus::CONFIRMED->value,
            LarpStageStatus::COMPLETED->value,
        ];

        $publicLarps = $this->larpRepository->createQueryBuilder('l')
            ->where('l.status IN (:publicStatuses)')
            ->setParameter('publicStatuses', $publicStatuses)
            ->getQuery()
            ->getResult();

        $publicIds = array_map(fn ($larp) => $larp->getId(), $publicLarps);

        $this->assertContains($publishedLarp->getId(), $publicIds, 'Published LARP should be in public list');
        $this->assertContains($inquiriesLarp->getId(), $publicIds, 'Inquiries LARP should be in public list');
        $this->assertNotContains($draftLarp->getId(), $publicIds, 'Draft LARP should not be in public list');
        $this->assertNotContains($wipLarp->getId(), $publicIds, 'WIP LARP should not be in public list');
    }

    public function test_count_organizer_larps_for_user(): void
    {
        $organizer = $this->createApprovedUser();

        $this->createDraftLarp($organizer, 'LARP 1');
        $this->createDraftLarp($organizer, 'LARP 2');

        // Count organizer LARPs
        $count = $this->larpRepository->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->andWhere('JSON_CONTAINS(lp.roles, :organizerRole) = 1')
            ->setParameter('user', $organizer)
            ->setParameter('organizerRole', json_encode(ParticipantRole::ORGANIZER->value))
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals(2, $count, 'User should have 2 LARPs as organizer');
    }

    public function test_find_larps_by_status(): void
    {
        $organizer = $this->createApprovedUser();

        $this->createDraftLarp($organizer, 'Draft 1');
        $this->createDraftLarp($organizer, 'Draft 2');
        $this->createPublishedLarp($organizer, 'Published 1');

        $draftLarps = $this->larpRepository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', LarpStageStatus::DRAFT->value)
            ->getQuery()
            ->getResult();

        $this->assertGreaterThanOrEqual(2, count($draftLarps), 'Should find at least 2 draft LARPs');
    }

    public function test_find_larps_where_user_is_player(): void
    {
        $organizer = $this->createApprovedUser('organizer@example.com');
        $player = $this->createApprovedUser('player@example.com');

        $larp1 = $this->createDraftLarp($organizer, 'LARP 1');
        $larp2 = $this->createDraftLarp($organizer, 'LARP 2');

        // Add player to larp1
        $this->addParticipantToLarp($larp1, $player, [ParticipantRole::PLAYER]);

        $playerLarps = $this->larpRepository->createQueryBuilder('l')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->andWhere('JSON_CONTAINS(lp.roles, :playerRole) = 1')
            ->setParameter('user', $player)
            ->setParameter('playerRole', json_encode(ParticipantRole::PLAYER->value))
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $playerLarps, 'Player should participate in 1 LARP');
        $this->assertEquals($larp1->getId(), $playerLarps[0]->getId());
    }

    public function test_find_larps_where_user_is_organizer(): void
    {
        $organizer = $this->createApprovedUser('organizer@example.com');
        $otherUser = $this->createApprovedUser('other@example.com');

        $larp1 = $this->createDraftLarp($organizer, 'LARP 1');
        $larp2 = $this->createDraftLarp($otherUser, 'LARP 2');

        $organizerLarps = $this->larpRepository->createQueryBuilder('l')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->andWhere('JSON_CONTAINS(lp.roles, :organizerRole) = 1')
            ->setParameter('user', $organizer)
            ->setParameter('organizerRole', json_encode(ParticipantRole::ORGANIZER->value))
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $organizerLarps, 'User should organize 1 LARP');
        $this->assertEquals($larp1->getId(), $organizerLarps[0]->getId());
    }

    public function test_find_larps_with_participants_count(): void
    {
        $organizer = $this->createApprovedUser('organizer@example.com');
        $player1 = $this->createApprovedUser('player1@example.com');
        $player2 = $this->createApprovedUser('player2@example.com');

        $larp = $this->createDraftLarp($organizer, 'LARP with Participants');

        // Add players
        $this->addParticipantToLarp($larp, $player1, [ParticipantRole::PLAYER]);
        $this->addParticipantToLarp($larp, $player2, [ParticipantRole::PLAYER]);

        // Query with participant count
        $result = $this->larpRepository->createQueryBuilder('l')
            ->select('l', 'COUNT(lp.id) as participantCount')
            ->leftJoin('l.larpParticipants', 'lp')
            ->where('l.id = :larpId')
            ->setParameter('larpId', $larp->getId())
            ->groupBy('l.id')
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals(3, $result['participantCount'], 'LARP should have 3 participants (organizer + 2 players)');
    }

    public function test_find_future_larps(): void
    {
        $organizer = $this->createApprovedUser();

        // Create future LARP
        $futureLarp = $this->createPublishedLarp($organizer, 'Future LARP');

        $futureLarps = $this->larpRepository->createQueryBuilder('l')
            ->where('l.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        $futureIds = array_map(fn ($larp) => $larp->getId(), $futureLarps);

        $this->assertContains(
            $futureLarp->getId(),
            $futureIds,
            'Future LARP should be in future LARPs list'
        );
    }

    public function test_find_larps_by_date_range(): void
    {
        $organizer = $this->createApprovedUser();

        $larp = $this->createPublishedLarp($organizer, 'LARP in Range');

        $startDate = new \DateTime('-1 month');
        $endDate = new \DateTime('+2 months');

        $larpsInRange = $this->larpRepository->createQueryBuilder('l')
            ->where('l.startDate >= :startDate')
            ->andWhere('l.startDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $rangeIds = array_map(fn ($larp) => $larp->getId(), $larpsInRange);

        $this->assertContains(
            $larp->getId(),
            $rangeIds,
            'LARP should be in date range'
        );
    }

    public function test_repository_respects_entity_manager_clear(): void
    {
        $organizer = $this->createApprovedUser();
        $larp = $this->createDraftLarp($organizer);

        $larpId = $larp->getId();

        // Clear entity manager
        $this->getEntityManager()->clear();

        // Find again
        $reloadedLarp = $this->larpRepository->find($larpId);

        $this->assertNotNull($reloadedLarp);
        $this->assertEquals($larpId, $reloadedLarp->getId());
        $this->assertEquals(LarpStageStatus::DRAFT, $reloadedLarp->getStatus());
    }

    public function test_user_organizer_larp_count_method(): void
    {
        $organizer = $this->createApprovedUser();

        $initialCount = $organizer->getOrganizerLarpCount();

        $this->createDraftLarp($organizer, 'LARP 1');
        $this->createDraftLarp($organizer, 'LARP 2');

        // Clear and reload user to get fresh count
        $this->getEntityManager()->clear();
        $reloadedUser = $this->getEntityManager()->find(
            \App\Domain\Account\Entity\User::class,
            $organizer->getId()
        );

        $newCount = $reloadedUser->getOrganizerLarpCount();

        $this->assertEquals(
            $initialCount + 2,
            $newCount,
            'Organizer LARP count should increase by 2'
        );
    }
}
