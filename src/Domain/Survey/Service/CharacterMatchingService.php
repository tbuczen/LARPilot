<?php

declare(strict_types=1);

namespace App\Domain\Survey\Service;

use App\Domain\Core\Entity\Enum\Gender;
use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Enum\CharacterType;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\Survey\Entity\SurveyResponse;

class CharacterMatchingService
{
    public function __construct(
        private readonly CharacterRepository $characterRepository
    ) {
    }

    /**
     * Generate character match suggestions for a survey response.
     *
     * @return list<array<string, mixed>> Array of match suggestions with character IDs and scores
     */
    public function generateMatchSuggestions(SurveyResponse $response): array
    {
        $larp = $response->getLarp();

        // Extract tags from survey answers
        $preferredTags = $this->extractPreferredTags($response);
        $unwantedTags = $this->extractUnwantedTags($response);

        // Load eligible characters (Player type or available for recruitment)
        $characters = $this->characterRepository->createQueryBuilder('c')
            ->where('c.larp = :larp')
            ->andWhere('(c.characterType = :playerType OR c.availableForRecruitment = true)')
            ->setParameter('larp', $larp)
            ->setParameter('playerType', CharacterType::Player)
            ->getQuery()
            ->getResult();

        // Calculate match score for each character
        $matches = [];
        foreach ($characters as $character) {
            $score = $this->calculateMatchScore($character, $preferredTags, $unwantedTags, $response);

            // Exclude characters with unwanted tags (blocking condition)
            if ($score === null) {
                continue;
            }

            $matches[] = [
                'characterId' => $character->getId()->toRfc4122(),
                'characterTitle' => $character->getTitle(),
                'score' => $score,
                'matchReasons' => $this->getMatchReasons($character, $preferredTags),
            ];
        }

        // Sort by score descending
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Return top 5 suggestions
        return array_slice($matches, 0, 5);
    }

    /**
     * Calculate match score for a character.
     *
     * @param Tag[] $preferredTags
     * @param Tag[] $unwantedTags
     *
     * @return int|null Match score, or null if character has unwanted tags (blocking condition)
     */
    private function calculateMatchScore(
        Character $character,
        array $preferredTags,
        array $unwantedTags,
        SurveyResponse $response
    ): ?int {
        $score = 0;
        $characterTags = $character->getTags()->toArray();
        $characterTagIds = array_map(fn (Tag $tag) => $tag->getId()->toRfc4122(), $characterTags);

        // BLOCKING: Exclude if character has any unwanted tags
        foreach ($unwantedTags as $unwantedTag) {
            if (in_array($unwantedTag->getId()->toRfc4122(), $characterTagIds, true)) {
                return null; // Block this character
            }
        }

        // +10 points per matching preferred tag
        foreach ($preferredTags as $preferredTag) {
            if (in_array($preferredTag->getId()->toRfc4122(), $characterTagIds, true)) {
                $score += 10;
            }
        }

        // +5 points for gender preference match
        $genderPreference = $this->extractGenderPreference($response);
        if ($character->getGender() === $genderPreference) {
            $score += 5;
        }

        // +3 points for complexity match (if rating question answered)
        $complexityPreference = $this->extractComplexityPreference($response);
        if ($complexityPreference !== null) {
            $characterComplexity = $this->estimateCharacterComplexity($character);
            $complexityDiff = abs($complexityPreference - $characterComplexity);
            if ($complexityDiff <= 1) {
                $score += 3;
            }
        }

        return $score;
    }

    /**
     * Extract preferred tags from survey answers.
     *
     * @return Tag[]
     */
    private function extractPreferredTags(SurveyResponse $response): array
    {
        $tags = [];

        foreach ($response->getAnswers() as $answer) {
            $question = $answer->getQuestion();
            if ($question->getQuestionText() === 'What themes or elements are you most interested in?') {
                $tags = array_merge($tags, $answer->getSelectedTags()->toArray());
            }
        }

        return $tags;
    }

    /**
     * Extract unwanted tags from survey answers.
     *
     * @return Tag[]
     */
    private function extractUnwantedTags(SurveyResponse $response): array
    {
        $tags = [];

        foreach ($response->getAnswers() as $answer) {
            $question = $answer->getQuestion();
            if ($question->getQuestionText() === 'Are there any themes or elements you want to avoid?') {
                $tags = array_merge($tags, $answer->getSelectedTags()->toArray());
            }
        }

        return $tags;
    }

    /**
     * Extract gender preference from survey answers.
     */
    private function extractGenderPreference(SurveyResponse $response): ?Gender
    {
        foreach ($response->getAnswers() as $answer) {
            $question = $answer->getQuestion();
            if ($question->getQuestionText() === 'Do you have a preferred character gender?') {
                $selectedOptions = $answer->getSelectedOptions();
                if ($selectedOptions->count() > 0) {
                    $optionText = $selectedOptions->first()->getOptionText();
                    // Map option text to Gender enum values
                    return match ($optionText) {
                        'Male character' => Gender::Male,
                        'Female character' => Gender::Female,
                        'Non-binary character' => Gender::Other,
                        default => null,
                    };
                }
            }
        }

        return null;
    }

    /**
     * Extract complexity preference (1-5 rating).
     */
    private function extractComplexityPreference(SurveyResponse $response): ?int
    {
        foreach ($response->getAnswers() as $answer) {
            $question = $answer->getQuestion();
            if ($question->getQuestionText() === 'How complex do you want your character to be?') {
                $answerText = $answer->getAnswerText();
                if ($answerText !== null && is_numeric($answerText)) {
                    return (int) $answerText;
                }
            }
        }

        return null;
    }

    /**
     * Estimate character complexity based on relationships and threads.
     */
    private function estimateCharacterComplexity(Character $character): int
    {
        $threadCount = $character->getThreads()->count();
        $questCount = $character->getQuests()->count();
        $factionCount = $character->getFactions()->count();

        $totalConnections = $threadCount + $questCount + $factionCount;

        // Map connection count to 1-5 scale
        return match (true) {
            $totalConnections === 0 => 1,
            $totalConnections <= 2 => 2,
            $totalConnections <= 5 => 3,
            $totalConnections <= 10 => 4,
            default => 5,
        };
    }

    /**
     * Get human-readable match reasons.
     *
     * @param Tag[] $preferredTags
     *
     * @return string[]
     */
    private function getMatchReasons(Character $character, array $preferredTags): array
    {
        $reasons = [];
        $characterTags = $character->getTags()->toArray();
        $characterTagIds = array_map(fn (Tag $tag) => $tag->getId()->toRfc4122(), $characterTags);

        foreach ($preferredTags as $preferredTag) {
            if (in_array($preferredTag->getId()->toRfc4122(), $characterTagIds, true)) {
                $reasons[] = sprintf('Matches your interest in: %s', $preferredTag->getTitle());
            }
        }

        return $reasons;
    }
}
