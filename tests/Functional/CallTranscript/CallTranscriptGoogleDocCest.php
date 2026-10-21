<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\Enum\CallOutputStatus;
use App\Domain\CallTranscript\Entity\Enum\CallRecordingStatus;
use App\Domain\Integrations\Entity\SharedFile;
use Tests\Support\CallTranscript\CallTranscriptFlow;
use Tests\Support\CallTranscript\CraigArchiveBuilder;
use Tests\Support\CallTranscript\FakeLlmProvider;
use Tests\Support\CallTranscript\FakeSpeechToTextProvider;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\Factory\Integrations\SharedFileFactory;
use Tests\Support\Factory\StoryObject\FactionFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Integrations\FixtureGoogleClientFactory;

class CallTranscriptGoogleDocCest
{
    use CallTranscriptFlow;

    public function _before(FunctionalTester $I): void
    {
        FakeSpeechToTextProvider::reset();
        FakeLlmProvider::reset();
        FixtureGoogleClientFactory::reset();
    }

    public function googleDocIsCreatedInChosenFolderAndLinked(FunctionalTester $I): void
    {
        $I->wantTo('get a Google Doc copy of the summary and transcript in the chosen Drive folder');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $ravens = FactionFactory::new()->forLarp($larp)->withTitle('Kruki')->create();
        $folder = $this->connectDrive($larp, $organizer);
        FakeLlmProvider::respondWith(['summary' => 'Kruki zdradzą.', 'decisions' => ['Zdrada w akcie 2']]);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Planowanie', [
            '1-anna.flac' => '1|Kruki zdradzą',
            'info.txt' => CraigArchiveBuilder::info(),
        ], options: ['summary' => true, 'googleDocFolder' => $folder->getId()->toRfc4122()]);
        $this->startTranscription($I, 1);

        $recording = $this->findRecording($I, 'Planowanie');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertSame(CallOutputStatus::DONE, $recording->getGoogleDocStatus());
        $I->assertSame('created-file-1', $recording->getGoogleDocId());
        $I->assertSame('https://docs.google.com/document/d/created-file-1/edit', $recording->getGoogleDocUrl());

        $uploads = FixtureGoogleClientFactory::uploads();
        $I->assertCount(1, $uploads);
        $I->assertSame('POST', $uploads[0]['method']);
        $body = $uploads[0]['body'];
        $I->assertStringContainsString(sprintf('"parents":["%s"]', $folder->getFileId()), $body);
        $I->assertStringContainsString('"mimeType":"application\/vnd.google-apps.document"', $body);
        $I->assertStringContainsString('"name":' . json_encode('Planowanie – 01-10-2026'), $body);
        $I->assertSame(1, preg_match('/Content-Transfer-Encoding: base64\r\n\r\n([A-Za-z0-9+\/=]+)/', $body, $htmlPart));
        $html = (string) base64_decode($htmlPart[1], true);
        $I->assertStringContainsString('<h1>Planowanie</h1><p>Nagrano: 01-10-2026 18:00</p><h2>Podsumowanie</h2><p>Kruki zdradzą.</p>', $html);
        $I->assertStringContainsString(sprintf('<a href="http://localhost/backoffice/larp/%s/story/faction/%s">Kruki</a>', $larp->getId()->toRfc4122(), $ravens->getId()->toRfc4122()), $html);
        $I->assertStringContainsString('[00:00:01] anna: Kruki zdradzą', $html);

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeElement('[data-call-output="google_doc"][data-call-output-status="done"] a[href="https://docs.google.com/document/d/created-file-1/edit"]');
        $I->see('Open in Google Docs');
    }

    public function googleDocFailureKeepsTranscriptAndCanBeRetried(FunctionalTester $I): void
    {
        $I->wantTo('keep the transcript when the Google Doc cannot be created and retry it');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);
        $folder = $this->connectDrive($larp, $organizer);
        FixtureGoogleClientFactory::failUploadsWith(403);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Drive down', ['1-anna.flac' => '1|Działa'], options: ['googleDocFolder' => $folder->getId()->toRfc4122()]);
        $this->startTranscription($I, 1);

        $recording = $this->findRecording($I, 'Drive down');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertSame('<p>[00:00:01] anna: Działa</p>', $recording->getKnowledgeDocument()?->getContent());
        $I->assertSame(CallOutputStatus::FAILED, $recording->getGoogleDocStatus());

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->seeElement('[data-call-output="google_doc"][data-call-output-status="failed"]');
        $I->see('The user has not granted the app access to the folder.');

        FixtureGoogleClientFactory::failUploadsWith(null);
        $this->submitAndDrain($I, 'form[name="call_recording_retry_google_doc"]');

        $I->assertSame(CallOutputStatus::DONE, $this->findRecording($I, 'Drive down')->getGoogleDocStatus());
        $I->assertCount(1, FixtureGoogleClientFactory::uploads());
    }

    public function noGoogleDocWithoutDriveIntegration(FunctionalTester $I): void
    {
        $I->wantTo('not offer a Google Doc when Google Drive is not connected');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        $I->dontSeeElement('[name="call_recording_upload[googleDocFolder]"]');

        $this->upload($I, $larp->getId()->toRfc4122(), 'No Drive', ['1-anna.flac' => '1|Bez Dysku']);
        $this->startTranscription($I, 1);

        $I->assertSame(CallOutputStatus::SKIPPED, $this->findRecording($I, 'No Drive')->getGoogleDocStatus());
        $I->assertSame([], FixtureGoogleClientFactory::requests());
    }

    private function connectDrive(mixed $larp, mixed $organizer): SharedFile
    {
        $integration = LarpIntegrationFactory::new()->google()->forLarp($larp)->createdBy($organizer)->create();
        SharedFileFactory::new()->forIntegration($integration)->spreadsheet()->create(['fileName' => 'Characters']);

        return SharedFileFactory::new()->forIntegration($integration)->folder()->create(['fileName' => 'Transkrypcje'])->_real();
    }
}
