<?php

namespace App\Service\Larp\Workflow;

use App\Entity\Larp;

class LarpTransitionGuardService
{
    /**
     * Check if LARP can be published
     */
    public function canPublish(Larp $larp): bool
    {
        return $this->hasRequiredBasicInfo($larp);
    }

    /**
     * Check if LARP can be opened for inquiries
     */
    public function canOpenForInquiries(Larp $larp): bool
    {
        return $this->hasRequiredBasicInfo($larp) && $this->hasAllCharactersWithShortDescription($larp);
    }

    /**
     * Check if LARP can be confirmed
     */
    public function canConfirm(Larp $larp): bool
    {
        return $this->hasRequiredBasicInfo($larp) &&
               $this->hasAllCharactersWithShortDescription($larp) &&
               $this->hasAllCharactersAssigned($larp);
    }

    /**
     * Get validation errors for a specific transition
     */
    public function getValidationErrors(Larp $larp, string $transitionName): array
    {
        $errors = [];

        $errors = match ($transitionName) {
            'to_published' => $this->getPublishValidationErrors($larp),
            'to_inquiries' => $this->getInquiriesValidationErrors($larp),
            'to_confirmed' => $this->getConfirmedValidationErrors($larp),
            default => $errors,
        };

        return $errors;
    }

    /**
     * Check if LARP has required basic information
     */
    private function hasRequiredBasicInfo(Larp $larp): bool
    {
        return $larp->getLocation() instanceof \App\Entity\Location &&
               !in_array($larp->getDescription(), [null, '', '0'], true) &&
               $larp->getStartDate() instanceof \DateTimeInterface &&
               $larp->getEndDate() instanceof \DateTimeInterface;
    }

    /**
     * Check if all characters have short descriptions
     */
    private function hasAllCharactersWithShortDescription(Larp $larp): bool
    {
        $characters = $larp->getCharacters();
        
        if ($characters->isEmpty()) {
            return false; // Need at least one character
        }

        foreach ($characters as $character) {
            if (empty($character->getDescription())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if all characters have participants assigned
     */
    private function hasAllCharactersAssigned(Larp $larp): bool
    {
        $characters = $larp->getCharacters();

        if ($characters->isEmpty()) {
            return false;
        }

        foreach ($characters as $character) {
            if ($character->getLarpParticipant() === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get count of unassigned characters
     */
    private function getUnassignedCharactersCount(Larp $larp): int
    {
        $characters = $larp->getCharacters();
        $unassignedCount = 0;

        foreach ($characters as $character) {
            if ($character->getLarpParticipant() === null) {
                $unassignedCount++;
            }
        }

        return $unassignedCount;
    }

    /**
     * Get validation errors for publishing
     */
    private function getPublishValidationErrors(Larp $larp): array
    {
        $errors = [];

        if (!$larp->getLocation() instanceof \App\Entity\Location) {
            $errors[] = 'Location is required to publish';
        }

        if (in_array($larp->getDescription(), [null, '', '0'], true)) {
            $errors[] = 'Description is required to publish';
        }

        if (!$larp->getStartDate() instanceof \DateTimeInterface) {
            $errors[] = 'Start date is required to publish';
        }

        if (!$larp->getEndDate() instanceof \DateTimeInterface) {
            $errors[] = 'End date is required to publish';
        }

        return $errors;
    }

    /**
     * Get validation errors for opening inquiries
     */
    private function getInquiriesValidationErrors(Larp $larp): array
    {
        $errors = $this->getPublishValidationErrors($larp);

        if ($larp->getCharacters()->isEmpty()) {
            $errors[] = 'At least one character is required to open for inquiries';
        }

        $charactersWithoutDescription = $larp->getCharacters()->filter(fn ($character): bool => in_array($character->getDescription(), [null, '', '0'], true));

        if (!$charactersWithoutDescription->isEmpty()) {
            $errors[] = sprintf(
                '%d character(s) are missing descriptions',
                $charactersWithoutDescription->count()
            );
        }

        return $errors;
    }

    /**
     * Get validation errors for confirming
     */
    private function getConfirmedValidationErrors(Larp $larp): array
    {
        $errors = $this->getInquiriesValidationErrors($larp);

        $charactersCount = $larp->getCharacters()->count();
        $unassignedCount = $this->getUnassignedCharactersCount($larp);

        if ($unassignedCount > 0) {
            $errors[] = sprintf(
                '%d character(s) still need participants assigned',
                $unassignedCount
            );
        }

        return $errors;
    }
}
