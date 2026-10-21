<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Entity\Enum\CallRecordingStatus;
use App\Domain\CallTranscript\Entity\Enum\CallTrackStatus;
use App\Domain\CallTranscript\Message\TranscribeCallTrackMessage;
use App\Domain\CallTranscript\MessageHandler\TranscribeCallTrackHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Tests\Support\CallTranscript\CallTranscriptFlow;
use Tests\Support\CallTranscript\FakeSpeechToTextProvider;
use Tests\Support\CallTranscript\Mp3FrameBuilder;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\FunctionalTester;

class CallTranscriptLongCallCest
{
    use CallTranscriptFlow;

    public function _before(FunctionalTester $I): void
    {
        FakeSpeechToTextProvider::reset();
    }

    public function longMp3IsSplitAndTimestampsAreShifted(FunctionalTester $I): void
    {
        $I->wantTo('split a too large MP3 track into parts and keep the timeline');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Long call', ['1-anna.mp3' => Mp3FrameBuilder::build(100)]);
        $this->startTranscription($I, 1);

        $I->assertCount(3, FakeSpeechToTextProvider::$calls);
        $recording = $this->findRecording($I, 'Long call');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertStringContainsString(
            '<p>[00:00:00] anna: mp3 part of 19599 bytes</p><p>[00:00:01] anna: mp3 part of 19599 bytes</p><p>[00:00:02] anna: mp3 part of 2502 bytes</p>',
            (string) $recording->getKnowledgeDocument()?->getContent(),
        );
    }

    public function rateLimitedTrackIsRetriedThenFailed(FunctionalTester $I): void
    {
        $I->wantTo('retry a rate limited track with backoff and fail it after the last attempt');

        $organizer = UserFactory::createApprovedUser();
        $larp = LarpFactory::createDraftLarp($organizer);

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Busy provider', [
            '1-anna.flac' => '1|Działa',
            '2-tomek.flac' => FakeSpeechToTextProvider::RATE_LIMITED_AUDIO,
        ]);
        $this->startTranscription($I, 2);

        $I->assertCount(1, $this->retriedMessages);
        $recording = $this->findRecording($I, 'Busy provider');
        $I->assertSame(CallRecordingStatus::TRANSCRIBING, $recording->getStatus());
        $tomek = $I->grabEntityFromRepository(CallRecordingTrack::class, ['speaker' => 'tomek']);
        $I->assertSame(CallTrackStatus::PENDING, $tomek->getStatus());
        $I->assertSame(1, $tomek->getAttempts());
        $I->assertNotNull($tomek->getAudioFilename());

        $retriedMessage = $this->retriedMessages[0];
        $I->assertInstanceOf(TranscribeCallTrackMessage::class, $retriedMessage);
        $handler = $I->grabService(TranscribeCallTrackHandler::class);
        for ($attempt = 2; $attempt < TranscribeCallTrackHandler::MAX_ATTEMPTS; ++$attempt) {
            try {
                $handler($retriedMessage);
                $I->fail('A rate limited attempt should ask Messenger to retry.');
            } catch (RecoverableMessageHandlingException $retry) {
                $I->assertGreaterThan(TranscribeCallTrackHandler::RETRY_DELAY_MILLISECONDS, $retry->getRetryDelay());
            }
        }

        $handler($retriedMessage);
        $this->drainAsyncTransport($I);

        $recording = $this->findRecording($I, 'Busy provider');
        $I->assertSame(CallRecordingStatus::COMPLETED, $recording->getStatus());
        $I->assertStringContainsString('tomek: Speech-to-text provider is temporarily unavailable', (string) $recording->getFailureReason());
        $I->assertSame('<p>[00:00:01] anna: Działa</p>', $recording->getKnowledgeDocument()?->getContent());
    }
}
