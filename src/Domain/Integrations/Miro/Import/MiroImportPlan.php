<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

final readonly class MiroImportPlan
{
    /**
     * @param list<MiroImportSuggestion> $suggestions
     * @param list<MiroMembershipHint> $membershipHints
     */
    public function __construct(
        public array $suggestions,
        public array $membershipHints,
    ) {
    }

    /**
     * @return list<MiroImportSuggestion>
     */
    public function changes(): array
    {
        return array_values(array_filter($this->suggestions, static fn (MiroImportSuggestion $suggestion): bool => $suggestion->isApplicable()));
    }

    /**
     * @return list<MiroImportSuggestion>
     */
    public function notImportable(): array
    {
        return array_values(array_filter(
            $this->suggestions,
            static fn (MiroImportSuggestion $suggestion): bool => !$suggestion->isApplicable() && $suggestion->action !== MiroImportAction::Unchanged,
        ));
    }

    public function unchangedCount(): int
    {
        return count(array_filter($this->suggestions, static fn (MiroImportSuggestion $suggestion): bool => $suggestion->action === MiroImportAction::Unchanged));
    }

    public function suggestionWithToken(string $token): ?MiroImportSuggestion
    {
        foreach ($this->changes() as $suggestion) {
            if ($suggestion->token() === $token) {
                return $suggestion;
            }
        }

        return null;
    }
}
