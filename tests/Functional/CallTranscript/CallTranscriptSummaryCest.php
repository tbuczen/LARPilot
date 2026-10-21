<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Entity\Enum\CallRecordingStatus;
use Tests\Support\CallTranscript\CallTranscriptFlow;
use Tests\Support\CallTranscript\FakeLlmProvider;
use Tests\Support\CallTranscript\FakeSpeechToTextProvider;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\StoryObject\CharacterFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\Factory\StoryObject\ThreadFactory;
use Tests\Support\FunctionalTester;

class CallTranscriptSummaryCest
{
    use CallTranscriptFlow;

    public function _before(FunctionalTester $I): void
    {
        FakeSpeechToTextProvider::reset();
        FakeLlmProvider::reset();
    }

    public function summaryIsAddedOnTopWithLinkedStoryObjects(FunctionalTester $I): void
    {
        $I->wantTo('get an AI summary at the top of the transcript with links to the story objects that were mentioned');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $ravens = FactionFactory::new()->forLarp($larp)->withTitle('Kruki')->create();
        $king = CharacterFactory::new()->forLarp($larp)->create(['title' => 'Król Aldred']);
        CharacterFactory::new()->forLarp($larp)->create(['title' => 'Never mentioned']);
        ThreadFactory::new()->forLarp($larp)->create(['title' => 'Zdrada w zamku', 'createdBy' => $organizer]);
        FakeLlmProvider::respondWith([
            'summary' => 'Ustalono, że Kruki zdradzą króla w drugim akcie.',
            'decisions' => ['Kruki zdradzają króla Aldreda'],
            'action_items' => [['task' => 'Napisać wstęp frakcji', 'person' => 'anna']],
            'open_questions' => ['Kto ostrzeże króla?'],
            'mentioned' => ['Zdrada w zamku', 'Król Aldred'],
        ]);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Planowanie aktu 2', [
            '1-anna.flac' => '1|Kruki zdradzą króla Aldreda',
        ], options: ['summary' => true]);
        $this->startTranscription($I, 1);

        $recording = $this->findRecording($I, 'Planowanie aktu 2');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertSame(CallOutputStatus::DONE, $recording->getSummaryStatus());
        $I->assertCount(1, FakeLlmProvider::$calls);
        $I->assertStringContainsString('Write in Polish.', FakeLlmProvider::$calls[0][0]->content);

        $content = (string) $recording->getKnowledgeDocument()?->getContent();
        $larpId = $larp->getId()->toRfc4122();
        $I->assertStringStartsWith('<h2>Podsumowanie</h2><p>Ustalono, że Kruki zdradzą króla w drugim akcie.</p>', $content);
        $I->assertStringContainsString('<h3>Decyzje</h3><ul><li>Kruki zdradzają króla Aldreda</li></ul>', $content);
        $I->assertStringContainsString('<h3>Zadania</h3><ul><li>Napisać wstęp frakcji – <strong>anna</strong></li></ul>', $content);
        $I->assertStringContainsString('<h3>Otwarte pytania</h3><ul><li>Kto ostrzeże króla?</li></ul>', $content);
        $I->assertStringContainsString(sprintf('<a href="/backoffice/larp/%s/story/character/%s">Król Aldred</a> (Postać)', $larpId, $king->getId()->toRfc4122()), $content);
        $I->assertStringContainsString(sprintf('<a href="/backoffice/larp/%s/story/faction/%s">Kruki</a> (Frakcja)', $larpId, $ravens->getId()->toRfc4122()), $content);
        $I->assertStringContainsString('>Zdrada w zamku</a> (Wątek)', $content);
        $I->assertStringNotContainsString('Never mentioned', $content);
        $I->assertStringEndsWith('<h2>Transkrypcja</h2><p>[00:00:01] anna: Kruki zdradzą króla Aldreda</p>', $content);

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larpId]);
        $I->seeElement('[data-call-output="summary"][data-call-output-status="done"]');
    }

    public function summaryFailureKeepsTranscriptAndCanBeRetried(FunctionalTester $I): void
    {
        $I->wantTo('keep the transcript when the summary fails and retry the summary');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        FakeLlmProvider::$failure = 'Rate limit reached';

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Summary fails', ['1-anna.flac' => '1|Działa'], options: ['summary' => true]);
        $this->startTranscription($I, 1);

        $recording = $this->findRecording($I, 'Summary fails');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertSame(CallOutputStatus::FAILED, $recording->getSummaryStatus());
        $I->assertSame('<p>[00:00:01] anna: Działa</p>', $recording->getKnowledgeDocument()?->getContent());
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeElement('[data-call-output="summary"][data-call-output-status="failed"]');
        $I->see('The AI model failed: Rate limit reached');

        FakeLlmProvider::$failure = null;
        FakeLlmProvider::respondWith(['summary' => 'Drugie podejście.']);
        $this->submitAndDrain($I, 'form[name="call_recording_retry_summary"]');
        $I->see('Started again');

        $recording = $this->findRecording($I, 'Summary fails');
        $I->assertSame(CallOutputStatus::DONE, $recording->getSummaryStatus());
        $I->assertSame('<h2>Podsumowanie</h2><p>Drugie podejście.</p><h2>Transkrypcja</h2><p>[00:00:01] anna: Działa</p>', $recording->getKnowledgeDocument()?->getContent());
    }

    public function summaryIsSkippedWhenNotRequested(FunctionalTester $I): void
    {
        $I->wantTo('not call the AI model when the summary is switched off for an upload');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'No summary', ['1-anna.flac' => '1|Bez podsumowania']);
        $this->startTranscription($I, 1);

        $I->assertSame([], FakeLlmProvider::$calls);
        $I->assertSame(CallOutputStatus::SKIPPED, $this->findRecording($I, 'No summary')->getSummaryStatus());
    }
}
