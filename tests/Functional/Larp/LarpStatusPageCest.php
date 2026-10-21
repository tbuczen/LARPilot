<?php

declare(strict_types=1);

namespace Tests\Functional\Larp;

use App\Domain\Core\Entity\Enum\Locale;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

class LarpStatusPageCest
{
    public function organizerSeesWorkflowWithBootstrapIconsAndBadges(FunctionalTester $I): void
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('.workflow-step.active .badge.text-bg-primary');
        $I->seeElement('.workflow-arrow i.bi-arrow-down');
        $I->seeElement('#publishRequirements i.bi-check-circle-fill, #publishRequirements i.bi-x-circle-fill');
        $I->dontSeeElement('i.fas');
        $I->dontSeeElement('[class*="badge-"]');
    }

    public function polishUserSeesTranslatedStatusesAndTransitions(FunctionalTester $I): void
    {
        $organizer = UserFactory::createApprovedUser();
        $organizer->setPreferredLocale(Locale::PL);
        $I->getEntityManager()->flush();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Szkic', '.badge');
        $I->see('W trakcie prac', '.workflow-step .badge');
        $I->see('Opublikowany', '.workflow-step .badge');
        $I->see('Otwarty na zgłoszenia', '.workflow-step .badge');
        $I->see('Przenieś do „W trakcie prac”');
        $I->see('Postapokaliptyczny', 'select');
        $I->dontSee('Move to Work in Progress');
        $I->dontSee('Work in Progress');
    }

    public function englishUserSeesReadableStatusesAndTransitions(FunctionalTester $I): void
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_status_index', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->see('Draft', '.badge');
        $I->see('Move to Work in Progress');
    }
}
