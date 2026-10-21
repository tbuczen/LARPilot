<?php

namespace App\Domain\CallTranscript\Service\Summary;

use App\Domain\CallTranscript\DTO\CallSummary;
use App\Domain\StoryAI\DTO\ChatMessage;
use App\Domain\StoryAI\Service\Provider\LLMProviderInterface;
use JsonException;
use Throwable;

final readonly class CallSummaryGenerator
{
    public const MAX_CHUNK_CHARACTERS = 48000;
    private const CONTEXT_SHARE_FOR_TRANSCRIPT = 0.4;
    private const CHARACTERS_PER_TOKEN = 4;
    private const MAX_TITLE_LIST_CHARACTERS = 8000;
    private const TEMPERATURE = 0.2;

    private const LANGUAGE_NAMES = [
        'pl' => 'Polish',
        'en' => 'English',
    ];

    public function __construct(
        private LLMProviderInterface $llmProvider,
    ) {
    }

    /**
     * @param list<string> $transcriptLines
     * @param list<string> $storyObjectTitles
     *
     * @throws CallSummaryException
     */
    public function generate(array $transcriptLines, ?string $language, array $storyObjectTitles = []): CallSummary
    {
        if ($transcriptLines === []) {
            throw CallSummaryException::emptyTranscript();
        }

        $chunks = $this->chunk($transcriptLines);
        if (count($chunks) === 1) {
            return $this->ask($this->summaryInstructions($language, $storyObjectTitles), $chunks[0]);
        }

        $partialSummaries = [];
        foreach ($chunks as $index => $chunk) {
            $partialSummaries[] = $this->ask(
                $this->summaryInstructions($language, $storyObjectTitles, sprintf('This is part %d of %d of a longer call.', $index + 1, count($chunks))),
                $chunk,
            );
        }

        return $this->reduce($partialSummaries, $language);
    }

    public function chunkCharacters(): int
    {
        $contextCharacters = (int) ($this->llmProvider->getMaxContextLength() * self::CHARACTERS_PER_TOKEN * self::CONTEXT_SHARE_FOR_TRANSCRIPT);

        return max(1000, min(self::MAX_CHUNK_CHARACTERS, $contextCharacters));
    }

    /**
     * @param list<string> $lines
     *
     * @return list<string>
     */
    private function chunk(array $lines): array
    {
        $limit = $this->chunkCharacters();
        $chunks = [];
        $current = '';
        foreach ($lines as $line) {
            $line = mb_substr($line, 0, $limit - 1);
            if ($current !== '' && mb_strlen($current) + mb_strlen($line) + 1 > $limit) {
                $chunks[] = $current;
                $current = '';
            }

            $current .= ($current === '' ? '' : "\n") . $line;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @param list<CallSummary> $partialSummaries
     */
    private function reduce(array $partialSummaries, ?string $language): CallSummary
    {
        $groups = $this->chunk(array_map(self::encode(...), $partialSummaries));
        if (count($groups) === 1 || count($groups) >= count($partialSummaries)) {
            return $this->ask($this->reduceInstructions($language), implode("\n", array_map(self::encode(...), $partialSummaries)));
        }

        $mergedSummaries = [];
        foreach ($groups as $group) {
            $mergedSummaries[] = $this->ask($this->reduceInstructions($language), $group);
        }

        return $this->reduce($mergedSummaries, $language);
    }

    private function ask(string $instructions, string $content): CallSummary
    {
        try {
            $response = $this->llmProvider->complete(
                [ChatMessage::system($instructions), ChatMessage::user($content)],
                ['temperature' => self::TEMPERATURE, 'response_format' => ['type' => 'json_object']],
            );
        } catch (Throwable $failure) {
            throw CallSummaryException::providerFailed($failure);
        }

        try {
            $decoded = json_decode(trim($response), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CallSummaryException::invalidResponse();
        }

        if (!is_array($decoded)) {
            throw CallSummaryException::invalidResponse();
        }

        $summary = CallSummary::fromArray($decoded);
        if ($summary->summary === '') {
            throw CallSummaryException::invalidResponse();
        }

        return $summary;
    }

    /**
     * @param list<string> $storyObjectTitles
     */
    private function summaryInstructions(?string $language, array $storyObjectTitles, string $partNote = ''): string
    {
        $instructions = <<<'PROMPT'
            You summarize a voice call of LARP organizers planning their game. The user message is the transcript, one line per utterance: "[hh:mm:ss] Speaker: text". Lines spoken by "Note" are notes the organizers added during the call.
            %s
            %s
            Return only a JSON object with these keys:
            - "summary": 3 to 6 sentences on what the call was about and what came out of it;
            - "decisions": list of decisions that were made (strings);
            - "action_items": list of objects {"task": string, "person": string or null}, "person" only when the transcript names who does it;
            - "open_questions": list of questions left unanswered (strings);
            - "mentioned": list of names of characters, factions, threads, quests, places, items and events of the game that were talked about, spelled exactly as in the list of known names when they appear there.
            Use empty lists when there is nothing to put in them. Do not invent anything that is not in the transcript.
            PROMPT;

        $knownNames = '';
        $titles = implode('; ', array_unique($storyObjectTitles));
        if ($titles !== '') {
            $knownNames = 'Known names in this game: ' . mb_substr($titles, 0, self::MAX_TITLE_LIST_CHARACTERS);
        }

        return trim(sprintf($instructions, $this->languageInstruction($language), trim($partNote . "\n" . $knownNames)));
    }

    private function reduceInstructions(?string $language): string
    {
        $instructions = <<<'PROMPT'
            The user message holds partial summaries of consecutive parts of one long call of LARP organizers, one JSON object per line, in call order.
            Merge them into one summary of the whole call. Remove duplicates, keep every decision, action item and open question, and drop questions that a later part answered.
            %s
            Return only a JSON object with the keys "summary" (3 to 6 sentences), "decisions", "action_items" (objects {"task", "person"}), "open_questions" and "mentioned".
            PROMPT;

        return trim(sprintf($instructions, $this->languageInstruction($language)));
    }

    private function languageInstruction(?string $language): string
    {
        $languageName = $language === null ? null : self::LANGUAGE_NAMES[$language] ?? null;
        if ($languageName === null) {
            return 'Write in the language most of the transcript is spoken in; if unsure, write in Polish.';
        }

        return sprintf('Write in %s.', $languageName);
    }

    private static function encode(CallSummary $summary): string
    {
        $data = $summary->toArray();
        unset($data['story_object_ids']);

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
