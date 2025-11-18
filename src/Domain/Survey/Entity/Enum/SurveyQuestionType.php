<?php

declare(strict_types=1);

namespace App\Domain\Survey\Entity\Enum;

enum SurveyQuestionType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SINGLE_CHOICE = 'single_choice';
    case MULTIPLE_CHOICE = 'multiple_choice';
    case RATING = 'rating';
    case TAG_SELECTION = 'tag_selection';

    public function getLabel(): string
    {
        return match ($this) {
            self::TEXT => 'Short Text',
            self::TEXTAREA => 'Long Text',
            self::SINGLE_CHOICE => 'Single Choice',
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::RATING => 'Rating Scale',
            self::TAG_SELECTION => 'Tag Selection',
        };
    }

    public function requiresOptions(): bool
    {
        return match ($this) {
            self::SINGLE_CHOICE, self::MULTIPLE_CHOICE => true,
            default => false,
        };
    }

    public function allowsTags(): bool
    {
        return $this === self::TAG_SELECTION;
    }
}
