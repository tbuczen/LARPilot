<?php

declare(strict_types=1);

namespace Tests\Functional\StoryObject\Relation;

use App\Domain\StoryObject\Entity\Enum\RelationType as RelationKind;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\Relation;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class RelationFormRenderingCest
{
    public function lockedOwnerSideIsNotRenderedOnTheStoryObjectPage(FunctionalTester $I): void
    {
        $I->wantTo('verify the locked owner fields are hidden on the story object page');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $owner = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Owner Character')
            ->create()
            ->_real();

        $target = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Target Character')
            ->create()
            ->_real();

        $relation = (new Relation())
            ->setRelationType(RelationKind::Friend)
            ->setFromType(TargetType::Character)
            ->setFrom($owner)
            ->setToType(TargetType::Character)
            ->setTo($target);
        $relation->setLarp($larp->_real());
        $relation->setCreatedBy($organizer);
        $relation->setTitle('Old friends');

        $entityManager = $I->getEntityManager();
        $entityManager->persist($relation);
        $entityManager->flush();

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_story_character_modify', [
            'larp' => $larp->getId(),
            'character' => $owner->getId(),
        ]);
        $I->seeResponseCodeIsSuccessful();

        $I->dontSeeElement('select[name="relation[fromType]"]');
        $I->dontSeeElement('select[name="relation[from]"]');
        $I->seeElement('select[name="relation[toType]"]');
        $I->seeElement('select[name="relation[to]"]');
    }
}
