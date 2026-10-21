<?php

namespace App\Domain\CallTranscript\Service\Speaker;

use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\LarpParticipant;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CallSpeakerLabeler
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function label(CallRecordingTrack $track, ?string $locale = null): string
    {
        $participant = $track->getParticipant();
        if (!$participant instanceof LarpParticipant) {
            return $track->getSpeaker();
        }

        return $this->participantLabel($participant, $locale) ?? $track->getSpeaker();
    }

    public function participantLabel(LarpParticipant $participant, ?string $locale = null): ?string
    {
        $name = CallSpeakerMatcher::stripDiscordIdSuffix((string) $participant->getName());
        if ($name === '') {
            return null;
        }

        $role = $this->primaryRole($participant);
        if ($role === null) {
            return $name;
        }

        return sprintf('%s (%s)', $name, $this->translator->trans('user_role.' . $role->value, [], 'messages', $locale));
    }

    private function primaryRole(LarpParticipant $participant): ?ParticipantRole
    {
        $roles = $participant->getRoles();
        foreach (ParticipantRole::cases() as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return null;
    }
}
