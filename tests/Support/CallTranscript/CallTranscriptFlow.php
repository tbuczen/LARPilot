<?php

declare(strict_types=1);

namespace Tests\Support\CallTranscript;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Message\ExportCallTranscriptToGoogleDocMessage;
use App\Domain\CallTranscript\Message\FinalizeCallTranscriptMessage;
use App\Domain\CallTranscript\Message\GenerateCallSummaryMessage;
use App\Domain\CallTranscript\Message\TranscribeCallTrackMessage;
use App\Domain\CallTranscript\MessageHandler\ExportCallTranscriptToGoogleDocHandler;
use App\Domain\CallTranscript\MessageHandler\FinalizeCallTranscriptHandler;
use App\Domain\CallTranscript\MessageHandler\GenerateCallSummaryHandler;
use App\Domain\CallTranscript\MessageHandler\TranscribeCallTrackHandler;
use App\Domain\CallTranscript\Service\CallRecordingStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Support\FunctionalTester;

trait CallTranscriptFlow
{
    private ?string $archiveFilename = null;

    private int $handledMessages = 0;

    private ?InMemoryTransport $transport = null;

    /**
     * @var list<object>
     */
    private array $retriedMessages = [];

    public function _after(FunctionalTester $I): void
    {
        if ($this->archiveFilename !== null) {
            (new Filesystem())->remove(codecept_data_dir($this->archiveFilename));
            $this->archiveFilename = null;
        }
    }

    /**
     * @param array<string, string> $entries
     * @param array<string, bool|string> $options
     */
    private function upload(FunctionalTester $I, string $larpId, string $title, array $entries, string $language = 'pl', array $options = []): void
    {
        $archive = CraigArchiveBuilder::build($entries);
        $this->archiveFilename = 'call-transcript-' . bin2hex(random_bytes(6)) . '.zip';
        (new Filesystem())->rename($archive, codecept_data_dir($this->archiveFilename));

        $I->amOnRoute('backoffice_larp_call_transcript_list', ['larp' => $larpId]);
        $I->fillField('call_recording_upload[title]', $title);
        $I->selectOption('call_recording_upload[language]', $language);
        $I->attachFile('call_recording_upload[archive]', $this->archiveFilename);
        $options += ['summary' => false];
        foreach ($options as $field => $value) {
            $fieldName = sprintf('call_recording_upload[%s]', $field);
            match (true) {
                $value === true => $I->checkOption($fieldName),
                $value === false => $I->uncheckOption($fieldName),
                default => $I->selectOption($fieldName, $value),
            };
        }
        $I->click('form[name="call_recording_upload"] button[type="submit"]');
    }

    private function findRecording(FunctionalTester $I, string $title): CallRecording
    {
        $entityManager = $I->getEntityManager();
        $entityManager->clear();
        $recording = $entityManager->getRepository(CallRecording::class)->findOneBy(['title' => $title]);
        $I->assertInstanceOf(CallRecording::class, $recording);

        return $recording;
    }

    private function startTranscription(FunctionalTester $I, int $expectedTracks): void
    {
        $I->stopFollowingRedirects();
        $I->click('form[name="call_recording_start"] button[type="submit"]');
        $I->seeResponseCodeIsRedirection();

        $I->assertCount($expectedTracks, $this->pendingMessages($I, TranscribeCallTrackMessage::class));
        $this->drainAsyncTransport($I);

        $I->followRedirect();
        $I->startFollowingRedirects();
    }

    private function submitAndDrain(FunctionalTester $I, string $formSelector): void
    {
        $I->stopFollowingRedirects();
        $I->submitForm($formSelector, []);
        $I->seeResponseCodeIsRedirection();
        $this->drainAsyncTransport($I);
        $I->followRedirect();
        $I->startFollowingRedirects();
    }

    /**
     * @param class-string $messageClass
     *
     * @return list<object>
     */
    private function pendingMessages(FunctionalTester $I, string $messageClass): array
    {
        $messages = [];
        foreach (array_slice($this->asyncTransport($I)->getSent(), $this->handledMessages) as $envelope) {
            if ($envelope->getMessage() instanceof $messageClass) {
                $messages[] = $envelope->getMessage();
            }
        }

        return $messages;
    }

    private function drainAsyncTransport(FunctionalTester $I): void
    {
        $I->getEntityManager()->clear();
        $transport = $this->asyncTransport($I);

        while (($envelope = $transport->getSent()[$this->handledMessages] ?? null) !== null) {
            ++$this->handledMessages;
            $message = $envelope->getMessage();
            $handlerClass = self::handlerFor($message);
            if ($handlerClass === null) {
                continue;
            }

            try {
                $I->grabService($handlerClass)($message);
            } catch (RecoverableMessageHandlingException) {
                $this->retriedMessages[] = $message;
            }
        }
    }

    /**
     * @return class-string|null
     */
    private static function handlerFor(object $message): ?string
    {
        return match ($message::class) {
            TranscribeCallTrackMessage::class => TranscribeCallTrackHandler::class,
            FinalizeCallTranscriptMessage::class => FinalizeCallTranscriptHandler::class,
            GenerateCallSummaryMessage::class => GenerateCallSummaryHandler::class,
            ExportCallTranscriptToGoogleDocMessage::class => ExportCallTranscriptToGoogleDocHandler::class,
            default => null,
        };
    }

    private function storage(FunctionalTester $I): CallRecordingStorage
    {
        return $I->grabService(CallRecordingStorage::class);
    }

    private function asyncTransport(FunctionalTester $I): InMemoryTransport
    {
        $transport = $I->grabService('messenger.transport.async');
        if ($this->transport !== $transport) {
            $this->transport = $transport;
            $this->handledMessages = 0;
            $this->retriedMessages = [];
        }

        return $transport;
    }
}
