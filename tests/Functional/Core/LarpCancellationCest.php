<?php

declare(strict_types=1);

namespace Tests\Functional\Core;

use App\Domain\Application\Entity\Enum\SubmissionStatus;
use App\Domain\Application\Entity\LarpApplication;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Symfony\Component\Uid\Uuid;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Application\LarpApplicationFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class LarpCancellationCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
    }

    /**
     * @return list<array{status: LarpStageStatus}>
     */
    protected function cancellableStatuses(): array
    {
        return [
            ['status' => LarpStageStatus::DRAFT],
            ['status' => LarpStageStatus::WIP],
            ['status' => LarpStageStatus::PUBLISHED],
            ['status' => LarpStageStatus::INQUIRIES],
            ['status' => LarpStageStatus::CONFIRMED],
        ];
    }

    /**
     * @return list<array{status: LarpStageStatus}>
     */
    protected function terminalStatuses(): array
    {
        return [
            ['status' => LarpStageStatus::COMPLETED],
            ['status' => LarpStageStatus::CANCELLED],
        ];
    }

    #[DataProvider('cancellableStatuses')]
    public function organizerCancelsLarpFromEveryOpenStage(FunctionalTester $I, Example $example): void
    {
        $I->wantTo('cancel a LARP as its organizer from every open stage');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, $example['status']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('#cancelLarpModal form[name="larp_cancel"]');
        $I->dontSeeElement(sprintf('form[action="%s"]:not([name="larp_cancel"])', $this->cancelUrl($I, $larp->getId())));

        $I->submitForm('form[name="larp_cancel"]', []);

        $I->seeResponseCodeIsRedirection();
        $I->assertSame(LarpStageStatus::CANCELLED, $this->reloadLarp($I, $larp->getId())->getStatus());
    }

    #[DataProvider('terminalStatuses')]
    public function completedOrCancelledLarpCannotBeCancelled(FunctionalTester $I, Example $example): void
    {
        $I->wantTo('see that a completed or cancelled LARP cannot be cancelled');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, $example['status']);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);
        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeElement('#cancelLarpModal');

        $I->sendAjaxPostRequest($this->cancelUrl($I, $larp->getId()), ['_token' => 'irrelevant']);

        $I->seeResponseCodeIs(403);
        $I->assertSame($example['status'], $this->reloadLarp($I, $larp->getId())->getStatus());
    }

    public function playerCannotCancelLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify a player gets 403 when cancelling a LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::INQUIRIES);
        $player = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($player)->player()->create();

        $this->assertCancellationForbidden($I, $player, $larp->getId());
    }

    public function storyWriterCannotCancelLarp(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer gets 403 when cancelling a LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CONFIRMED);
        $storyWriter = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($storyWriter)->storyWriter()->create();

        $this->assertCancellationForbidden($I, $storyWriter, $larp->getId());
    }

    public function cancellationRequiresValidCsrfToken(FunctionalTester $I): void
    {
        $I->wantTo('keep the LARP open when the cancel request has no valid CSRF token');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::PUBLISHED);

        $I->amLoggedInAs($organizer);
        $I->sendAjaxPostRequest($this->cancelUrl($I, $larp->getId()), ['_token' => 'invalid']);

        $I->seeResponseCodeIsRedirection();
        $I->assertSame(LarpStageStatus::PUBLISHED, $this->reloadLarp($I, $larp->getId())->getStatus());
    }

    public function publicPageShowsCancelledNoticeWithoutApplying(FunctionalTester $I): void
    {
        $I->wantTo('see a cancelled notice and no application form on the public page of a cancelled LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CANCELLED);
        $visitor = UserFactory::createApprovedUser();

        $I->amLoggedInAs($visitor);
        $I->amOnRoute('public_larp_details', ['slug' => $larp->getSlug()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('[data-test="larp-cancelled-notice"]');
        $I->dontSeeElement(sprintf('a[href="%s"]', $I->getUrl('public_larp_application_create', ['larp' => $larp->getId()])));
    }

    public function publicPageOfOpenLarpHasNoCancelledNotice(FunctionalTester $I): void
    {
        $I->wantTo('see no cancelled notice on a LARP that is open for inquiries');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::INQUIRIES);

        $I->amLoggedInAs(UserFactory::createApprovedUser());
        $I->amOnRoute('public_larp_details', ['slug' => $larp->getSlug()]);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeElement('[data-test="larp-cancelled-notice"]');
    }

    public function applicationFormIsClosedForCancelledLarp(FunctionalTester $I): void
    {
        $I->wantTo('be redirected away from the application form of a cancelled LARP');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CANCELLED);
        $applicant = UserFactory::createApprovedUser();

        $I->amLoggedInAs($applicant);
        $I->amOnRoute('public_larp_application_create', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsRedirection();
        $I->followRedirect();
        $I->seeCurrentRouteIs('public_larp_details', ['slug' => $larp->getSlug()]);
        $I->see('Applications for this LARP are closed.');
        $I->assertSame(0, $I->getEntityManager()->getRepository(LarpApplication::class)->count(['larp' => $larp->getId()]));
    }

    public function offeredCharacterCannotBeConfirmedForCancelledLarp(FunctionalTester $I): void
    {
        $I->wantTo('be unable to confirm an offered character once the LARP is cancelled');

        $organizer = UserFactory::createApprovedUser();
        $larp = $this->createLarp($organizer, LarpStageStatus::CANCELLED);
        $applicant = UserFactory::createApprovedUser();
        $character = CharacterFactory::new()->forLarp($larp)->create();
        $application = LarpApplicationFactory::new()->forLarp($larp)->forUser($applicant)->offered()->create();

        $I->amLoggedInAs($applicant);
        $I->sendAjaxPostRequest($I->getUrl('public_larp_application_confirm_character', [
            'larp' => $larp->getId(),
            'application' => $application->getId(),
            'character' => $character->getId(),
        ]));

        $I->seeResponseCodeIsRedirection();
        $I->getEntityManager()->clear();
        $reloadedApplication = $I->getEntityManager()->getRepository(LarpApplication::class)->find($application->getId());
        $I->assertSame(SubmissionStatus::OFFERED, $reloadedApplication?->getStatus());
    }

    private function assertCancellationForbidden(FunctionalTester $I, mixed $user, Uuid $larpId): void
    {
        $I->amLoggedInAs($user);
        $I->sendAjaxPostRequest($this->cancelUrl($I, $larpId), ['_token' => 'irrelevant']);

        $I->seeResponseCodeIs(403);
        $I->assertNotSame(LarpStageStatus::CANCELLED, $this->reloadLarp($I, $larpId)->getStatus());
    }

    private function createLarp(mixed $organizer, LarpStageStatus $status): mixed
    {
        $larp = LarpFactory::new()->withStatus($status)->withCreator($organizer)->create();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($organizer)->organizer()->create();

        return $larp;
    }

    private function cancelUrl(FunctionalTester $I, Uuid $larpId): string
    {
        return $I->getUrl('backoffice_larp_status_transition', ['larp' => $larpId, 'transitionName' => 'to_cancelled']);
    }

    private function reloadLarp(FunctionalTester $I, Uuid $larpId): Larp
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $larp = $entityManager->getRepository(Larp::class)->find($larpId);
        $I->assertInstanceOf(Larp::class, $larp);

        return $larp;
    }
}
