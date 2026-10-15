<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\CallRecording;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\FunctionalTester;

class CallTranscriptAccessCest
{
    public function organizerCanOpenCallTranscripts(FunctionalTester $I): void
    {
        $I->wantTo('verify an organizer can open the call transcripts page');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeElement('form[name="call_recording_upload"]');
    }

    public function playerCannotOpenCallTranscripts(FunctionalTester $I): void
    {
        $I->wantTo('verify a player gets 403 on the call transcripts page');

        $this->assertParticipantDenied($I, 'player');
    }

    public function storyWriterCannotOpenCallTranscripts(FunctionalTester $I): void
    {
        $I->wantTo('verify a story writer gets 403 on the call transcripts page');

        $this->assertParticipantDenied($I, 'storyWriter');
    }

    public function playerCannotOpenAnotherLarpsRecordingReview(FunctionalTester $I): void
    {
        $I->wantTo('verify a player cannot open the speaker review of a recording');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $recording = new CallRecording($larp->_real(), 'Planning call', 'pl');
        $recording->setCreatedBy($organizer);
        $entityManager = $I->getEntityManager();
        $entityManager->persist($recording);
        $entityManager->flush();

        $player = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($player)->player()->create();

        $I->amLoggedInAs($player);
        $I->amOnRoute('backoffice_larp_call_transcript_review', ['larp' => $larp->getId(), 'recording' => $recording->getId()]);

        $I->seeResponseCodeIs(403);
    }

    private function assertParticipantDenied(FunctionalTester $I, string $role): void
    {
        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $participant = UserFactory::createApprovedUser();
        LarpParticipantFactory::new()->forLarp($larp)->forUser($participant)->{$role}()->create();

        $I->amLoggedInAs($participant);
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);

        $I->seeResponseCodeIs(403);
    }
}
