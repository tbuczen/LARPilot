<?php

namespace App\Domain\CallTranscript\Service\Discord;

use App\Domain\CallTranscript\DTO\CallSummary;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Service\CallTranscriptDocumentRenderer;
use App\Domain\CallTranscript\Service\Transcript\CallTranscriptLines;
use App\Domain\Integrations\Discord\Client\DiscordClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CallTranscriptDiscordMessages
{
    public function __construct(
        private CallTranscriptDocumentRenderer $documentRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<string>
     */
    public function build(CallRecording $recording): array
    {
        $locale = CallTranscriptLines::localeOf($recording);
        $lines = [];
        $summary = $recording->getSummary();
        if ($summary instanceof CallSummary) {
            $lines = $this->summaryLines($recording, $summary, $locale);
        }

        $knowledgeDocument = $recording->getKnowledgeDocument();
        $larp = $recording->getLarp();
        if ($knowledgeDocument !== null && $larp !== null) {
            $lines[] = '';
            $lines[] = $this->translator->trans('call_recording.discord.transcript_link', [
                '%url%' => '<' . $this->urlGenerator->generate('backoffice_larp_knowledge_modify', [
                    'larp' => $larp->getId(),
                    'document' => $knowledgeDocument->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL) . '>',
            ], 'call_transcript', $locale);
        }

        $googleDocUrl = $recording->getGoogleDocUrl();
        if ($googleDocUrl !== null) {
            $lines[] = $this->translator->trans('call_recording.discord.google_doc_link', ['%url%' => '<' . $googleDocUrl . '>'], 'call_transcript', $locale);
        }

        return self::split($lines);
    }

    /**
     * @param list<string> $lines
     *
     * @return list<string>
     */
    public static function split(array $lines, int $limit = DiscordClient::MAX_MESSAGE_LENGTH): array
    {
        $messages = [];
        $current = '';
        foreach ($lines as $line) {
            foreach (self::hardWrap($line, $limit) as $piece) {
                $candidate = $current === '' ? $piece : $current . "\n" . $piece;
                if (mb_strlen($candidate) <= $limit) {
                    $current = $candidate;
                    continue;
                }

                if (trim($current) !== '') {
                    $messages[] = trim($current);
                }
                $current = $piece;
            }
        }

        if (trim($current) !== '') {
            $messages[] = trim($current);
        }

        return $messages;
    }

    /**
     * @return list<string>
     */
    private function summaryLines(CallRecording $recording, CallSummary $summary, string $locale): array
    {
        $lines = ['**' . $this->documentRenderer->heading('summary', $locale) . '**', $summary->summary];
        $sections = [
            'decisions' => $summary->decisions,
            'action_items' => array_map(
                static fn (array $actionItem): string => $actionItem['task'] . ($actionItem['person'] === null ? '' : ' – **' . $actionItem['person'] . '**'),
                $summary->actionItems,
            ),
            'open_questions' => $summary->openQuestions,
            'story_objects' => array_map(
                fn (array $mention): string => sprintf(
                    '[%s](<%s>) (%s)',
                    str_replace(['[', ']'], '', (string) $mention['storyObject']->getTitle()),
                    $mention['url'],
                    $this->documentRenderer->storyObjectTypeLabel($mention['storyObject'], $locale),
                ),
                $this->documentRenderer->mentionedStoryObjects($recording, $summary, UrlGeneratorInterface::ABSOLUTE_URL),
            ),
        ];

        foreach ($sections as $section => $items) {
            if ($items === []) {
                continue;
            }

            $lines[] = '';
            $lines[] = '**' . $this->documentRenderer->heading($section, $locale) . '**';
            foreach ($items as $item) {
                $lines[] = '- ' . $item;
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function hardWrap(string $line, int $limit): array
    {
        if (mb_strlen($line) <= $limit) {
            return [$line];
        }

        return mb_str_split($line, $limit);
    }
}
