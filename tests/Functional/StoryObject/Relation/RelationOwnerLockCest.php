<?php

declare(strict_types=1);

namespace Tests\Functional\StoryObject\Relation;

use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Form\RelationType;
use Symfony\Component\Form\FormFactoryInterface;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\FunctionalTester;

class RelationOwnerLockCest
{
    public function ownerSideIsLockedToContextStoryObject(FunctionalTester $I): void
    {
        $I->wantTo('verify the relation owner is fixed to the story object the form is opened from');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $owner = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Owner Character')
            ->create()
            ->_real();

        $relation = (new Relation())
            ->setFromType($owner::getTargetType())
            ->setFrom($owner);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $I->grabService('form.factory');
        $form = $formFactory->create(RelationType::class, $relation, [
            'larp' => $larp->_real(),
            'contextOwner' => $owner,
        ]);

        $I->assertTrue(
            $form->get('fromType')->isDisabled(),
            'fromType must be locked when opened from a story object'
        );
        $I->assertTrue(
            $form->get('from')->isDisabled(),
            'from must be locked when opened from a story object'
        );
        $I->assertSame(
            $owner,
            $form->get('from')->getData(),
            'from must be prefilled with the context story object'
        );

        $ownerLabels = array_map(
            static fn ($choice) => $choice->label,
            $form->createView()->children['from']->vars['choices']
        );
        $I->assertContains(
            'Owner Character',
            $ownerLabels,
            'the context story object must be present in the owner choice list, not excluded from it'
        );
    }

    public function interpersonalRelationCannotTargetAnEvent(FunctionalTester $I): void
    {
        $I->wantTo('verify a character cannot be befriended to an event, thread or place');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $owner = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Owner Character')
            ->create()
            ->_real();

        $relation = (new Relation())
            ->setFromType($owner::getTargetType())
            ->setFrom($owner);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $I->grabService('form.factory');
        $form = $formFactory->create(RelationType::class, $relation, [
            'larp' => $larp->_real(),
            'contextOwner' => $owner,
        ]);

        $offeredTargetTypes = array_map(
            static fn ($choice) => $choice->label,
            $form->createView()->children['toType']->vars['choices']
        );

        $I->assertSame(
            ['Character', 'Faction'],
            $offeredTargetTypes,
            'a Friend relation from a Character must only offer Character and Faction as target types'
        );
    }

    public function submittingAnEventAsFriendTargetIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('verify the server rejects an incompatible target type even if the client sends one');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $owner = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Owner Character')
            ->create()
            ->_real();

        $relation = (new Relation())
            ->setFromType($owner::getTargetType())
            ->setFrom($owner);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $I->grabService('form.factory');
        $form = $formFactory->create(RelationType::class, $relation, [
            'larp' => $larp->_real(),
            'contextOwner' => $owner,
        ]);

        $form->submit([
            'title' => 'Befriending an event',
            'description' => 'should not be allowed',
            'relationType' => 'friend',
            'toType' => 'event',
        ]);

        $I->assertFalse(
            $form->isValid(),
            'submitting toType=event for a Friend relation from a Character must be rejected'
        );
    }

    public function targetSideStillExcludesTheOwnerItself(FunctionalTester $I): void
    {
        $I->wantTo('verify a story object still cannot be related to itself');

        $creator = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($creator);

        $owner = CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Owner Character')
            ->create()
            ->_real();

        CharacterFactory::new()
            ->forLarp($larp)
            ->withTitle('Other Character')
            ->create();

        $relation = (new Relation())
            ->setFromType($owner::getTargetType())
            ->setFrom($owner)
            ->setToType(TargetType::Character);

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $I->grabService('form.factory');
        $form = $formFactory->create(RelationType::class, $relation, [
            'larp' => $larp->_real(),
            'contextOwner' => $owner,
        ]);

        $targetLabels = array_map(
            static fn ($choice) => $choice->label,
            $form->createView()->children['to']->vars['choices']
        );

        $I->assertNotContains('Owner Character', $targetLabels, 'owner must not be relatable to itself');
        $I->assertContains('Other Character', $targetLabels, 'other characters must remain relatable');
    }
}
