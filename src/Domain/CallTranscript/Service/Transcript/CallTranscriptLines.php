<?php

namespace App\Domain\CallTranscript\Service\Transcript;

use App\Domain\CallTranscript\DTO\SpeakerTranscript;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Service\Speaker\CallSpeakerLabeler;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CallTranscriptLines
{
    public const DEFAULT_LOCALE = 'pl';

    public function __construct(
        private TranscriptMerger $merger,
        private CallSpeakerLabeler $speakerLabeler,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<string>
     */
    public function build(CallRecording $recording): array
    {
        $locale = self::localeOf($recording);
        $speakerTranscripts = [];
        foreach ($recording->getTracks() as $track) {
            if (!$track->isTranscribed()) {
                continue;
            }

            $speakerTranscripts[] = new SpeakerTranscript($this->speakerLabeler->label($track, $locale), $track->getSegments());
        }

        if ($speakerTranscripts === []) {
            return [];
        }

        $speakerTranscripts[] = new SpeakerTranscript(
            $this->translator->trans('call_recording.note_speaker', [], 'call_transcript', $locale),
            $recording->getNotes(),
        );

        return $this->merger->merge($speakerTranscripts);
    }

    public static function localeOf(CallRecording $recording): string
    {
        return $recording->getLanguage() ?? self::DEFAULT_LOCALE;
    }
}
