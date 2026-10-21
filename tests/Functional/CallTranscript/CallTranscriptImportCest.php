<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallRecordingStatus;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use Tests\Support\CallTranscript\CallTranscriptFlow;
use Tests\Support\CallTranscript\CraigArchiveBuilder;
use Tests\Support\CallTranscript\FakeSpeechToTextProvider;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

class CallTranscriptImportCest
{
    use CallTranscriptFlow;

    public function _before(FunctionalTester $I): void
    {
        FakeSpeechToTextProvider::reset();
    }

    public function uploadedRecordingBecomesOrganizerOnlyKnowledgeDocument(FunctionalTester $I): void
    {
        $I->wantTo('import a Craig recording, exclude a speaker and get a merged transcript');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Sesja planowania', [
            '1-anna.flac' => "1|Cześć wszystkim\n10|Zaczynamy od frakcji",
            '2-tomek.flac' => '5|Hej',
            '3-ewa.flac' => '7|Nie nagrywajcie mnie',
            '4-bartek.srt' => "1\n00:00:08,000 --> 00:00:09,000\nMam notatki",
            'info.txt' => CraigArchiveBuilder::info(),
        ]);

        $I->seeCurrentRouteIs('backoffice_larp_call_transcript_review');
        $I->seeNumberOfElements('[data-call-track]', 4);
        $recording = $this->findRecording($I, 'Sesja planowania');
        $recordingDirectory = $this->storage($I)->directoryFor($recording);
        $I->assertCount(4, glob($recordingDirectory . '/*') ?: []);

        $I->uncheckOption('[data-call-track="ewa"] input[type="checkbox"]');
        $this->startTranscription($I, 3);

        $I->seeCurrentRouteIs('backoffice_larp_call_transcript_list');
        $I->see('Transcription started');
        $I->assertDirectoryDoesNotExist($recordingDirectory);

        $I->assertSame(['1|Cześć wszystkim' . "\n" . '10|Zaczynamy od frakcji', '5|Hej'], array_column(FakeSpeechToTextProvider::$calls, 'audio'));
        $I->assertSame(['pl', 'pl'], array_column(FakeSpeechToTextProvider::$calls, 'language'));
        $I->assertDirectoryDoesNotExist($recordingDirectory);

        $recording = $this->findRecording($I, 'Sesja planowania');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertNull($recording->getFailureReason());
        $I->assertCount(3, $recording->getTracks());

        $document = $recording->getKnowledgeDocument();
        $I->assertInstanceOf(KnowledgeDocument::class, $document);
        $I->assertSame('Sesja planowania', $document->getTitle());
        $I->assertSame('call_transcript', $document->getCategory());
        $I->assertFalse($document->isPublic());
        $I->assertCount(0, $document->getVisibleTo());
        $I->assertTrue($document->getLarp()?->getId()->equals($larp->getId()));
        $I->assertSame(
            '<p>Nagrano: 01-10-2026 18:00</p>'
            . '<p>[00:00:01] anna: Cześć wszystkim</p>'
            . '<p>[00:00:04] Notatka: decision: Faction X betrays the king</p>'
            . '<p>[00:00:05] tomek: Hej</p>'
            . '<p>[00:00:08] bartek: Mam notatki</p>'
            . '<p>[00:00:10] anna: Zaczynamy od frakcji</p>',
            $document->getContent(),
        );

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeElement('[data-call-recording-status="completed"]');
    }

    public function craigTranscriptsAreUsedWithoutSpeechToText(FunctionalTester $I): void
    {
        $I->wantTo('use transcripts shipped in the Craig zip instead of calling speech-to-text');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Craig transcripts', [
            '1-anna.flac' => '1|never transcribed',
            '1-anna.vtt' => "WEBVTT\n\n00:00:02.000 --> 00:00:03.000\nZ Craiga",
            '2-tomek.flac' => '1|never transcribed',
            '2-tomek.txt' => '[00:00:01] Ja pierwszy',
        ]);
        $this->startTranscription($I, 2);

        $I->assertSame([], FakeSpeechToTextProvider::$calls);
        $document = $this->findRecording($I, 'Craig transcripts')->getKnowledgeDocument();
        $I->assertInstanceOf(KnowledgeDocument::class, $document);
        $I->assertSame('<p>[00:00:01] tomek: Ja pierwszy</p><p>[00:00:02] anna: Z Craiga</p>', $document->getContent());
    }

    public function failedTrackIsReportedAndOthersStillPublished(FunctionalTester $I): void
    {
        $I->wantTo('publish the transcript when one speaker fails and report that speaker');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Partial', [
            '1-anna.flac' => '2|Działa',
            '2-tomek.flac' => FakeSpeechToTextProvider::FAILING_AUDIO,
        ], 'auto');
        $this->startTranscription($I, 2);

        $I->assertSame([null, null], array_column(FakeSpeechToTextProvider::$calls, 'language'));
        $recording = $this->findRecording($I, 'Partial');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertStringContainsString('tomek: Fake provider failure.', (string) $recording->getFailureReason());
        $I->assertSame('<p>[00:00:02] anna: Działa</p>', $recording->getKnowledgeDocument()?->getContent());
    }

    public function discardingDeletesStoredAudio(FunctionalTester $I): void
    {
        $I->wantTo('discard an import and delete its audio');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Discard me', ['1-anna.flac' => '1|audio']);
        $recordingDirectory = $this->storage($I)->directoryFor($this->findRecording($I, 'Discard me'));

        $I->submitForm('form[name="call_recording_discard"]', []);

        $I->seeCurrentRouteIs('backoffice_larp_call_transcript_list');
        $I->assertDirectoryDoesNotExist($recordingDirectory);
        $I->dontSeeInRepository(CallRecording::class, ['title' => 'Discard me']);
    }

    public function invalidArchiveIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('see an error when the zip has no speaker tracks');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Empty', ['info.txt' => CraigArchiveBuilder::info()]);

        $I->seeCurrentRouteIs('backoffice_larp_call_transcript_list');
        $I->see('No speaker tracks were found in the archive.');
        $I->dontSeeInRepository(CallRecording::class, ['title' => 'Empty']);
    }
}
