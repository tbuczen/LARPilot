<?php

namespace App\Service\Larp\Workflow;

use App\Entity\Enum\SubmissionStatus;
use App\Entity\Larp;
use App\Entity\Enum\LarpStageStatus;
use App\Entity\LarpApplication;

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
               $this->hasConfirmedApplicationsForAllCharacters($larp);
    }

    /**
     * Get validation errors for a specific transition
     */
    public function getValidationErrors(Larp $larp, string $transitionName): array
    {
        $errors = [];

        switch ($transitionName) {
            case 'to_published':
                $errors = $this->getPublishValidationErrors($larp);
                break;
            case 'to_inquiries':
                $errors = $this->getInquiriesValidationErrors($larp);
                break;
            case 'to_confirmed':
                $errors = $this->getConfirmedValidationErrors($larp);
                break;
        }

        return $errors;
    }

    /**
     * Check if LARP has required basic information
     */
    private function hasRequiredBasicInfo(Larp $larp): bool
    {
        return !empty($larp->getLocation()) &&
               !empty($larp->getDescription()) &&
               $larp->getStartDate() !== null &&
               $larp->getEndDate() !== null;
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
            // Assuming LarpCharacter has a shortDescription property
            if (empty($character->getDescription())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if there are confirmed applications for all characters
     */
    private function hasConfirmedApplicationsForAllCharacters(Larp $larp): bool
    {
        $characters = $larp->getCharacters();

        if ($characters->isEmpty()) {
            return false;
        }

        $confirmedApplications = $larp->getApplications()->filter(function(LarpApplication $application) {
            return $application->getStatus() === SubmissionStatus::CONSIDER ||
                $application->getStatus() === SubmissionStatus::ACCEPTED; // Adjust based on your actual status values
        });
        //OR
        $emptyCharacters = [];

        foreach ($characters as $character) {
            if($character->getLarpParticipant() === null) {
                $emptyCharacters[] = $character->getId()->toRfc4122();
            }
        }

        if(!empty($emptyCharacters)) {
            return false;
        }

        // Check if we have enough confirmed applications
        // This logic might need adjustment based on your business rules
        return $confirmedApplications->count() >= $characters->count();
    }

    /**
     * Get validation errors for publishing
     */
    private function getPublishValidationErrors(Larp $larp): array
    {
        $errors = [];

        if (empty($larp->getLocation())) {
            $errors[] = 'Location is required to publish';
        }

        if (empty($larp->getDescription())) {
            $errors[] = 'Description is required to publish';
        }

        if ($larp->getStartDate() === null) {
            $errors[] = 'Start date is required to publish';
        }

        if ($larp->getEndDate() === null) {
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

        $charactersWithoutDescription = $larp->getCharacters()->filter(function($character) {
            return empty($character->getShortDescription());
        });

        if (!$charactersWithoutDescription->isEmpty()) {
            $errors[] = sprintf(
                '%d character(s) are missing short descriptions',
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

        $confirmedApplications = $larp->getApplications()->filter(function($application) {
            return $application->getStatus() === 'confirmed';
        });

        $charactersCount = $larp->getCharacters()->count();
        $confirmedCount = $confirmedApplications->count();

        if ($confirmedCount < $charactersCount) {
            $errors[] = sprintf(
                'Need %d more confirmed applications (%d confirmed, %d characters)',
                $charactersCount - $confirmedCount,
                $confirmedCount,
                $charactersCount
            );
        }

        return $errors;
    }
}
