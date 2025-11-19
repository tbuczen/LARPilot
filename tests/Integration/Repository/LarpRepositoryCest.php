<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Repository\LarpRepository;
use Tests\Support\FunctionalTester;

/**
 * Integration tests for LarpRepository
 *
 * Tests repository queries for LARP filtering and access control
 */
class LarpRepositoryCest
{
    private ?LarpRepository $larpRepository = null;

    public function _before(FunctionalTester $I): void
    {
        $this->larpRepository = $I->grabService(LarpRepository::class);
    }

    public function findAllReturnsAllLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify that findAll returns all LARPs');

        $organizer1 = $I->createApprovedUser('organizer1@example.com');
        $organizer2 = $I->createApprovedUser('organizer2@example.com');

        $larp1 = LarpFactory::createDraftLarp($organizer1, 'LARP 1');
        $larp2 = $I->createPublishedLarp($organizer2, 'LARP 2');

        $allLarps = $this->larpRepository->findAll();

        $I->assertGreaterThanOrEqual(2, count($allLarps), 'Should find at least 2 LARPs');
    }

    public function findByUserReturnsOnlyParticipatingLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify that findByUser returns only LARPs where user participates');

        $organizer1 = $I->createApprovedUser('organizer1@example.com');
        $organizer2 = $I->createApprovedUser('organizer2@example.com');

        $larp1 = LarpFactory::createDraftLarp($organizer1, 'LARP 1');
        $larp2 = LarpFactory::createDraftLarp($organizer2, 'LARP 2');

        // Find LARPs for organizer1
        $userLarps = $this->larpRepository->createQueryBuilder('l')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->setParameter('user', $organizer1)
            ->getQuery()
            ->getResult();

        $I->assertCount(1, $userLarps, 'User should only see their participating LARPs');
        $I->assertEquals($larp1->getId(), $userLarps[0]->getId());
    }

    public function findPubliclyVisibleLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify that query filters public LARPs correctly by status');

        $organizer = UserFactory::createApprovedUser();

        $draftLarp = LarpFactory::createDraftLarp($organizer, 'Draft');
        $wipLarp = $I->createWipLarp($organizer, 'WIP');
        $publishedLarp = $I->createPublishedLarp($organizer, 'Published');
        $inquiriesLarp = $I->createLarp($organizer, LarpStageStatus::INQUIRIES, 'Inquiries');

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

        $I->assertContains($publishedLarp->getId(), $publicIds, 'Published LARP should be in public list');
        $I->assertContains($inquiriesLarp->getId(), $publicIds, 'Inquiries LARP should be in public list');
        $I->assertNotContains($draftLarp->getId(), $publicIds, 'Draft LARP should not be in public list');
        $I->assertNotContains($wipLarp->getId(), $publicIds, 'WIP LARP should not be in public list');
    }

    public function countOrganizerLarpsForUser(FunctionalTester $I): void
    {
        $I->wantTo('verify that counting organizer LARPs works correctly');

        $organizer = UserFactory::createApprovedUser();

        LarpFactory::createDraftLarp($organizer, 'LARP 1');
        LarpFactory::createDraftLarp($organizer, 'LARP 2');

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

        $I->assertEquals(2, $count, 'User should have 2 LARPs as organizer');
    }

    public function findLarpsByStatus(FunctionalTester $I): void
    {
        $I->wantTo('verify that finding LARPs by status works correctly');

        $organizer = UserFactory::createApprovedUser();

        LarpFactory::createDraftLarp($organizer, 'Draft 1');
        LarpFactory::createDraftLarp($organizer, 'Draft 2');
        $I->createPublishedLarp($organizer, 'Published 1');

        $draftLarps = $this->larpRepository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', LarpStageStatus::DRAFT->value)
            ->getQuery()
            ->getResult();

        $I->assertGreaterThanOrEqual(2, count($draftLarps), 'Should find at least 2 draft LARPs');
    }

    public function findLarpsWhereUserIsPlayer(FunctionalTester $I): void
    {
        $I->wantTo('verify that finding LARPs where user is player works correctly');

        $organizer = $I->createApprovedUser('organizer@example.com');
        $player = $I->createApprovedUser('player@example.com');

        $larp1 = LarpFactory::createDraftLarp($organizer, 'LARP 1');
        $larp2 = LarpFactory::createDraftLarp($organizer, 'LARP 2');

        // Add player to larp1
        $I->addParticipantToLarp($larp1, $player, [ParticipantRole::PLAYER]);

        $playerLarps = $this->larpRepository->createQueryBuilder('l')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->andWhere('JSON_CONTAINS(lp.roles, :playerRole) = 1')
            ->setParameter('user', $player)
            ->setParameter('playerRole', json_encode(ParticipantRole::PLAYER->value))
            ->getQuery()
            ->getResult();

        $I->assertCount(1, $playerLarps, 'Player should participate in 1 LARP');
        $I->assertEquals($larp1->getId(), $playerLarps[0]->getId());
    }

    public function findLarpsWhereUserIsOrganizer(FunctionalTester $I): void
    {
        $I->wantTo('verify that finding LARPs where user is organizer works correctly');

        $organizer = $I->createApprovedUser('organizer@example.com');
        $otherUser = $I->createApprovedUser('other@example.com');

        $larp1 = LarpFactory::createDraftLarp($organizer, 'LARP 1');
        $larp2 = LarpFactory::createDraftLarp($otherUser, 'LARP 2');

        $organizerLarps = $this->larpRepository->createQueryBuilder('l')
            ->join('l.larpParticipants', 'lp')
            ->where('lp.user = :user')
            ->andWhere('JSON_CONTAINS(lp.roles, :organizerRole) = 1')
            ->setParameter('user', $organizer)
            ->setParameter('organizerRole', json_encode(ParticipantRole::ORGANIZER->value))
            ->getQuery()
            ->getResult();

        $I->assertCount(1, $organizerLarps, 'User should organize 1 LARP');
        $I->assertEquals($larp1->getId(), $organizerLarps[0]->getId());
    }

    public function findLarpsWithParticipantsCount(FunctionalTester $I): void
    {
        $I->wantTo('verify that querying LARPs with participant count works correctly');

        $organizer = $I->createApprovedUser('organizer@example.com');
        $player1 = $I->createApprovedUser('player1@example.com');
        $player2 = $I->createApprovedUser('player2@example.com');

        $larp = LarpFactory::createDraftLarp($organizer, 'LARP with Participants');

        // Add players
        $I->addParticipantToLarp($larp, $player1, [ParticipantRole::PLAYER]);
        $I->addParticipantToLarp($larp, $player2, [ParticipantRole::PLAYER]);

        // Query with participant count
        $result = $this->larpRepository->createQueryBuilder('l')
            ->select('l', 'COUNT(lp.id) as participantCount')
            ->leftJoin('l.larpParticipants', 'lp')
            ->where('l.id = :larpId')
            ->setParameter('larpId', $larp->getId())
            ->groupBy('l.id')
            ->getQuery()
            ->getSingleResult();

        $I->assertEquals(3, $result['participantCount'], 'LARP should have 3 participants (organizer + 2 players)');
    }

    public function findFutureLarps(FunctionalTester $I): void
    {
        $I->wantTo('verify that finding future LARPs works correctly');

        $organizer = UserFactory::createApprovedUser();

        // Create future LARP
        $futureLarp = $I->createPublishedLarp($organizer, 'Future LARP');

        $futureLarps = $this->larpRepository->createQueryBuilder('l')
            ->where('l.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        $futureIds = array_map(fn ($larp) => $larp->getId(), $futureLarps);

        $I->assertContains(
            $futureLarp->getId(),
            $futureIds,
            'Future LARP should be in future LARPs list'
        );
    }

    public function findLarpsByDateRange(FunctionalTester $I): void
    {
        $I->wantTo('verify that finding LARPs by date range works correctly');

        $organizer = UserFactory::createApprovedUser();

        $larp = $I->createPublishedLarp($organizer, 'LARP in Range');

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

        $I->assertContains(
            $larp->getId(),
            $rangeIds,
            'LARP should be in date range'
        );
    }

    public function repositoryRespectsEntityManagerClear(FunctionalTester $I): void
    {
        $I->wantTo('verify that repository respects entity manager clear');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $larpId = $larp->getId();

        // Clear entity manager
        $I->getEntityManager()->clear();

        // Find again
        $reloadedLarp = $this->larpRepository->find($larpId);

        $I->assertNotNull($reloadedLarp);
        $I->assertEquals($larpId, $reloadedLarp->getId());
        $I->assertEquals(LarpStageStatus::DRAFT, $reloadedLarp->getStatus());
    }

    public function userOrganizerLarpCountMethod(FunctionalTester $I): void
    {
        $I->wantTo('verify that user organizer LARP count method works correctly');

        $organizer = UserFactory::createApprovedUser();

        $initialCount = $organizer->getOrganizerLarpCount();

        LarpFactory::createDraftLarp($organizer, 'LARP 1');
        LarpFactory::createDraftLarp($organizer, 'LARP 2');

        // Clear and reload user to get fresh count
        $I->getEntityManager()->clear();
        $reloadedUser = $I->getEntityManager()->find(
            \App\Domain\Account\Entity\User::class,
            $organizer->getId()
        );

        $newCount = $reloadedUser->getOrganizerLarpCount();

        $I->assertEquals(
            $initialCount + 2,
            $newCount,
            'Organizer LARP count should increase by 2'
        );
    }
}
