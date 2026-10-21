<?php

namespace App\Domain\CallTranscript\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\CallTranscript\DTO\CallRecordingOptions;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\Enum\CallOutput;
use App\Domain\CallTranscript\Form\Type\CallRecordingUploadType;
use App\Domain\CallTranscript\Repository\CallRecordingRepository;
use App\Domain\CallTranscript\Security\Voter\CallTranscriptVoter;
use App\Domain\CallTranscript\Service\CallRecordingUploader;
use App\Domain\CallTranscript\Service\CallTranscriptionStarter;
use App\Domain\CallTranscript\Service\CallTranscriptOutputDispatcher;
use App\Domain\CallTranscript\Service\Import\CallRecordingImportException;
use App\Domain\CallTranscript\Service\Speaker\CallSpeakerLabeler;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/call-transcripts', name: 'backoffice_larp_call_transcript_')]
class CallTranscriptController extends BaseController
{
    public const START_CSRF_TOKEN = 'call_recording_start';
    public const DISCARD_CSRF_TOKEN = 'call_recording_discard';
    public const RETRY_CSRF_TOKEN = 'call_recording_retry';

    #[Route('', name: 'list', methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        Larp $larp,
        CallRecordingRepository $recordingRepository,
        CallRecordingUploader $uploader,
    ): Response {
        $this->denyAccessUnlessGranted(CallTranscriptVoter::MANAGE, $larp);

        $form = $this->createForm(CallRecordingUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $organizer = $this->getUser();
            $archive = $form->get('archive')->getData();
            if (!$organizer instanceof User || !$archive instanceof UploadedFile) {
                throw $this->createAccessDeniedException();
            }

            $language = (string) $form->get('language')->getData();
            try {
                $recording = $uploader->upload(
                    $larp,
                    $organizer,
                    (string) $form->get('title')->getData(),
                    $language === CallRecordingUploadType::AUTO_DETECT_LANGUAGE ? null : $language,
                    $archive->getPathname(),
                    new CallRecordingOptions((bool) $form->get('summary')->getData()),
                );
            } catch (CallRecordingImportException $failure) {
                $this->addFlash('error', $this->translator->trans($failure->getMessage(), [], 'call_transcript'));

                return $this->redirectToRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
            }

            return $this->redirectToRoute('backoffice_larp_call_transcript_review', [
                'larp' => $larp->getId(),
                'recording' => $recording->getId(),
            ]);
        }

        return $this->render('domain/call_transcript/call_recording/list.html.twig', [
            'larp' => $larp,
            'form' => $form->createView(),
            'recordings' => $recordingRepository->findForLarp($larp),
        ]);
    }

    #[Route('/{recording}', name: 'review', methods: ['GET'])]
    public function review(Larp $larp, CallRecording $recording, CallSpeakerLabeler $speakerLabeler): Response
    {
        $this->denyAccessUnlessGranted(CallTranscriptVoter::MANAGE, $larp);
        $this->assertBelongsToLarp($recording, $larp);

        $participantChoices = [];
        foreach ($larp->getParticipants() as $participant) {
            $label = $speakerLabeler->participantLabel($participant);
            if ($label !== null) {
                $participantChoices[$participant->getId()->toRfc4122()] = $label;
            }
        }
        asort($participantChoices, SORT_NATURAL | SORT_FLAG_CASE);

        return $this->render('domain/call_transcript/call_recording/review.html.twig', [
            'larp' => $larp,
            'recording' => $recording,
            'participantChoices' => $participantChoices,
        ]);
    }

    #[Route('/{recording}/start', name: 'start', methods: ['POST'])]
    public function start(
        Request $request,
        Larp $larp,
        CallRecording $recording,
        CallTranscriptionStarter $starter,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(CallTranscriptVoter::MANAGE, $larp);
        $this->assertBelongsToLarp($recording, $larp);
        if (!$this->isCsrfTokenValid(self::START_CSRF_TOKEN . '_' . $recording->getId()->toRfc4122(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$recording->isAwaitingReview()) {
            return $this->redirectToRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        }

        $includedTrackIds = array_values(array_filter(
            $request->request->all('speakers'),
            static fn (mixed $trackId): bool => is_string($trackId) && $trackId !== '',
        ));
        $participantIdsByTrackId = array_filter(
            $request->request->all('participants'),
            static fn (mixed $participantId, mixed $trackId): bool => is_string($participantId) && is_string($trackId),
            ARRAY_FILTER_USE_BOTH,
        );
        if ($starter->start($recording, $includedTrackIds, $participantIdsByTrackId) === 0) {
            $this->addFlash('warning', $this->translator->trans('call_recording.review.nothing_selected', [], 'call_transcript'));

            return $this->redirectToRoute('backoffice_larp_call_transcript_review', [
                'larp' => $larp->getId(),
                'recording' => $recording->getId(),
            ]);
        }

        $this->addFlash('success', $this->translator->trans('call_recording.review.started', [], 'call_transcript'));

        return $this->redirectToRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
    }

    #[Route('/{recording}/discard', name: 'discard', methods: ['POST'])]
    public function discard(
        Request $request,
        Larp $larp,
        CallRecording $recording,
        CallTranscriptionStarter $starter,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(CallTranscriptVoter::MANAGE, $larp);
        $this->assertBelongsToLarp($recording, $larp);
        if (!$this->isCsrfTokenValid(self::DISCARD_CSRF_TOKEN . '_' . $recording->getId()->toRfc4122(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($recording->isTranscribing()) {
            return $this->redirectToRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
        }

        $starter->discard($recording);
        $this->addFlash('success', $this->translator->trans('call_recording.discarded', [], 'call_transcript'));

        return $this->redirectToRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
    }

    #[Route('/{recording}/retry/{output}', name: 'retry', methods: ['POST'])]
    public function retry(
        Request $request,
        Larp $larp,
        CallRecording $recording,
        CallOutput $output,
        CallTranscriptOutputDispatcher $outputDispatcher,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(CallTranscriptVoter::MANAGE, $larp);
        $this->assertBelongsToLarp($recording, $larp);
        if (!$this->isCsrfTokenValid(self::RETRY_CSRF_TOKEN . '_' . $recording->getId()->toRfc4122() . '_' . $output->value, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($outputDispatcher->retry($recording, $output)) {
            $this->addFlash('success', $this->translator->trans('call_recording.output.retry_started', [], 'call_transcript'));
        }

        return $this->redirectToRoute('backoffice_larp_call_transcript_list', ['larp' => $larp->getId()]);
    }

    private function assertBelongsToLarp(CallRecording $recording, Larp $larp): void
    {
        if ($recording->getLarp()?->getId()->equals($larp->getId()) !== true) {
            throw $this->createNotFoundException();
        }
    }
}
