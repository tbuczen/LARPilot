<?php

namespace App\Domain\CallTranscript\DTO;

final readonly class CallSummary
{
    /**
     * @param list<string> $decisions
     * @param list<array{task: string, person: ?string}> $actionItems
     * @param list<string> $openQuestions
     * @param list<string> $mentions
     * @param list<string> $storyObjectIds
     */
    public function __construct(
        public string $summary,
        public array $decisions = [],
        public array $actionItems = [],
        public array $openQuestions = [],
        public array $mentions = [],
        public array $storyObjectIds = [],
    ) {
    }

    /**
     * @param list<string> $storyObjectIds
     */
    public function withStoryObjectIds(array $storyObjectIds): self
    {
        return new self($this->summary, $this->decisions, $this->actionItems, $this->openQuestions, $this->mentions, $storyObjectIds);
    }

    /**
     * @return array{summary: string, decisions: list<string>, action_items: list<array{task: string, person: ?string}>, open_questions: list<string>, mentioned: list<string>, story_object_ids: list<string>}
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'decisions' => $this->decisions,
            'action_items' => $this->actionItems,
            'open_questions' => $this->openQuestions,
            'mentioned' => $this->mentions,
            'story_object_ids' => $this->storyObjectIds,
        ];
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $actionItems = [];
        foreach (self::list($data['action_items'] ?? []) as $actionItem) {
            if (is_string($actionItem)) {
                $actionItem = ['task' => $actionItem];
            }

            if (!is_array($actionItem)) {
                continue;
            }

            $task = self::text($actionItem['task'] ?? null);
            if ($task === '') {
                continue;
            }

            $person = self::text($actionItem['person'] ?? null);
            $actionItems[] = ['task' => $task, 'person' => $person === '' ? null : $person];
        }

        return new self(
            self::text($data['summary'] ?? null),
            self::texts($data['decisions'] ?? []),
            $actionItems,
            self::texts($data['open_questions'] ?? []),
            self::texts($data['mentioned'] ?? []),
            self::texts($data['story_object_ids'] ?? []),
        );
    }

    /**
     * @return list<mixed>
     */
    private static function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * @return list<string>
     */
    private static function texts(mixed $value): array
    {
        return array_values(array_filter(array_map(self::text(...), self::list($value)), static fn (string $text): bool => $text !== ''));
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
