<?php

namespace App\Domain\Gallery\Security\Voter;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Gallery\Entity\Enum\GalleryVisibility;
use App\Domain\Gallery\Entity\Gallery;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LarpGalleryVoter extends Voter
{
    public const VIEW = 'VIEW_GALLERY';
    public const CREATE = 'CREATE_GALLERY';
    public const EDIT = 'EDIT_GALLERY';
    public const DELETE = 'DELETE_GALLERY';

    protected function supports(string $attribute, $subject): bool
    {
        if ($attribute === self::CREATE && $subject instanceof Larp) {
            return true;
        }

        if (in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]) && $subject instanceof Gallery) {
            return true;
        }

        return false;
    }

    /**
     * @param Larp|Gallery $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($user, $subject),
            self::VIEW => $this->canView($user, $subject),
            self::EDIT => $this->canEdit($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            default => false,
        };
    }

    private function canCreate(User $user, Larp $larp): bool
    {
        $userParticipant = $this->getUserParticipant($user, $larp);
        if (!$userParticipant) {
            return false;
        }

        // Organizers and photographers can create galleries
        return $userParticipant->isAdmin() || $userParticipant->isPhotographer();
    }

    private function canView(User $user, Gallery $gallery): bool
    {
        $visibility = $gallery->getVisibility();

        // Public galleries can be viewed by anyone
        if ($visibility === GalleryVisibility::PUBLIC) {
            return true;
        }

        $larp = $gallery->getLarp();
        $userParticipant = $this->getUserParticipant($user, $larp);

        if (!$userParticipant) {
            return false;
        }

        // Organizers-only galleries
        if ($visibility === GalleryVisibility::ORGANIZERS_ONLY) {
            return $userParticipant->isAdmin();
        }

        // Participants-only galleries - any participant can view
        return true;
    }

    private function canEdit(User $user, Gallery $gallery): bool
    {
        $larp = $gallery->getLarp();
        $userParticipant = $this->getUserParticipant($user, $larp);

        if (!$userParticipant) {
            return false;
        }

        // Organizers can edit any gallery
        if ($userParticipant->isAdmin()) {
            return true;
        }

        // Photographers can edit their own galleries
        return $gallery->getPhotographer()->getId() === $userParticipant->getId();
    }

    private function canDelete(User $user, Gallery $gallery): bool
    {
        // Same logic as edit
        return $this->canEdit($user, $gallery);
    }

    private function getUserParticipant(User $user, Larp $larp): ?LarpParticipant
    {
        $participants = $larp->getParticipants();
        /** @var LarpParticipant|false $userParticipant */
        $userParticipant = $participants->filter(
            fn (LarpParticipant $participant): bool =>
                $participant->getUser()->getId() === $user->getId()
        )->first();

        return $userParticipant ?: null;
    }
}
