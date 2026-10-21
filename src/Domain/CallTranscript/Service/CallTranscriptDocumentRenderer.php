<?php

namespace App\Domain\CallTranscript\Service;

use App\Domain\CallTranscript\DTO\CallSummary;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Service\Transcript\CallTranscriptLines;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Service\Helper\DateFormat;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use App\Domain\StoryObject\Service\StoryObjectRouter;
use DateTimeZone;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CallTranscriptDocumentRenderer
{
    public function __construct(
        private CallTranscriptLines $transcriptLines,
        private StoryObjectRepository $storyObjectRepository,
        private StoryObjectRouter $storyObjectRouter,
        private TranslatorInterface $translator,
    ) {
    }

    public function render(CallRecording $recording, int $linkType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $locale = CallTranscriptLines::localeOf($recording);
        $html = '';
        $startedAt = $recording->getRecordingStartedAt();
        if ($startedAt !== null) {
            $html .= $this->paragraph($this->translator->trans('call_recording.recorded_at', [
                '%date%' => $startedAt->setTimezone(new DateTimeZone(date_default_timezone_get()))->format(DateFormat::DATETIME),
            ], 'call_transcript', $locale));
        }

        $summary = $recording->getSummary();
        if ($summary instanceof CallSummary) {
            $html .= $this->renderSummary($recording, $summary, $locale, $linkType);
            $html .= '<h2>' . $this->escape($this->translator->trans('call_recording.document.transcript', [], 'call_transcript', $locale)) . '</h2>';
        }

        foreach ($this->transcriptLines->build($recording) as $line) {
            $html .= $this->paragraph($line);
        }

        return $html;
    }

    /**
     * @return list<array{storyObject: StoryObject, url: string}>
     */
    public function mentionedStoryObjects(CallRecording $recording, CallSummary $summary, int $linkType = UrlGeneratorInterface::ABSOLUTE_PATH): array
    {
        $larp = $recording->getLarp();
        $validIds = array_values(array_filter($summary->storyObjectIds, Uuid::isValid(...)));
        if (!$larp instanceof Larp || $validIds === []) {
            return [];
        }

        $storyObjectsById = [];
        foreach ($this->storyObjectRepository->findBy(['id' => $validIds, 'larp' => $larp]) as $storyObject) {
            $storyObjectsById[$storyObject->getId()->toRfc4122()] = $storyObject;
        }

        $mentioned = [];
        foreach ($validIds as $storyObjectId) {
            $storyObject = $storyObjectsById[$storyObjectId] ?? null;
            $url = $storyObject === null ? null : $this->storyObjectRouter->getEditUrl($storyObject, $larp, $linkType);
            if ($storyObject === null || $url === null) {
                continue;
            }

            $mentioned[] = ['storyObject' => $storyObject, 'url' => $url];
        }

        return $mentioned;
    }

    public function storyObjectTypeLabel(StoryObject $storyObject, string $locale): string
    {
        return $this->translator->trans('call_recording.document.type.' . $storyObject::getTargetType()->value, [], 'call_transcript', $locale);
    }

    private function renderSummary(CallRecording $recording, CallSummary $summary, string $locale, int $linkType): string
    {
        $html = '<h2>' . $this->escape($this->heading('summary', $locale)) . '</h2>' . $this->paragraph($summary->summary);
        $html .= $this->section($this->heading('decisions', $locale), array_map($this->escape(...), $summary->decisions));
        $html .= $this->section($this->heading('action_items', $locale), array_map(
            fn (array $actionItem): string => $this->escape($actionItem['task']) . ($actionItem['person'] === null ? '' : ' – <strong>' . $this->escape($actionItem['person']) . '</strong>'),
            $summary->actionItems,
        ));
        $html .= $this->section($this->heading('open_questions', $locale), array_map($this->escape(...), $summary->openQuestions));
        $html .= $this->section($this->heading('story_objects', $locale), array_map(
            fn (array $mention): string => sprintf(
                '<a href="%s">%s</a> (%s)',
                $this->escape($mention['url']),
                $this->escape((string) $mention['storyObject']->getTitle()),
                $this->escape($this->storyObjectTypeLabel($mention['storyObject'], $locale)),
            ),
            $this->mentionedStoryObjects($recording, $summary, $linkType),
        ));

        return $html;
    }

    public function heading(string $section, string $locale): string
    {
        return $this->translator->trans('call_recording.document.' . $section, [], 'call_transcript', $locale);
    }

    /**
     * @param list<string> $itemsHtml
     */
    private function section(string $heading, array $itemsHtml): string
    {
        if ($itemsHtml === []) {
            return '';
        }

        return '<h3>' . $this->escape($heading) . '</h3><ul>' . implode('', array_map(static fn (string $item): string => '<li>' . $item . '</li>', $itemsHtml)) . '</ul>';
    }

    private function paragraph(string $text): string
    {
        return '<p>' . $this->escape($text) . '</p>';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
