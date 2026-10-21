<?php

namespace App\Domain\CallTranscript\Service\Speaker;

use App\Domain\Account\Entity\UserSocialAccount;
use App\Domain\Account\Repository\UserSocialAccountRepository;
use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\CallTranscript\Service\Import\CraigInfoParser;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\Enum\SocialAccountProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final readonly class CallSpeakerMatcher
{
    private const MEMBER_SEARCH_LIMIT = 10;

    public function __construct(
        private UserSocialAccountRepository $socialAccountRepository,
        private LarpIntegrationRepository $integrationRepository,
        private DiscordClient $discordClient,
        private LoggerInterface $logger,
    ) {
    }

    public function proposeParticipants(CallRecording $recording): void
    {
        $larp = $recording->getLarp();
        if (!$larp instanceof Larp) {
            return;
        }

        $participants = $this->participantIndex($larp);
        $guildId = $this->connectedGuildId($larp);

        foreach ($recording->getTracks() as $track) {
            $track->setParticipant($this->match($track, $participants, $guildId));
        }
    }

    /**
     * @param array{byDiscordId: array<string, LarpParticipant>, byName: array<string, LarpParticipant>} $participants
     */
    private function match(CallRecordingTrack $track, array $participants, ?string $guildId): ?LarpParticipant
    {
        $discordUserId = $track->getDiscordUserId();
        if ($discordUserId !== null && isset($participants['byDiscordId'][$discordUserId])) {
            return $participants['byDiscordId'][$discordUserId];
        }

        $speakerKey = CraigInfoParser::speakerKey($track->getSpeaker());
        if (isset($participants['byName'][$speakerKey])) {
            return $participants['byName'][$speakerKey];
        }

        if ($guildId === null || $participants['byDiscordId'] === []) {
            return null;
        }

        foreach ($this->searchGuildMembers($guildId, $track->getSpeaker()) as $memberId => $memberNames) {
            if (!in_array($speakerKey, $memberNames, true) || !isset($participants['byDiscordId'][$memberId])) {
                continue;
            }

            $track->setDiscordUserId($memberId);

            return $participants['byDiscordId'][$memberId];
        }

        return null;
    }

    /**
     * @return array{byDiscordId: array<string, LarpParticipant>, byName: array<string, LarpParticipant>}
     */
    private function participantIndex(Larp $larp): array
    {
        $participantsByUserId = [];
        $byName = [];
        foreach ($larp->getParticipants() as $participant) {
            $user = $participant->getUser();
            if ($user === null) {
                continue;
            }

            $participantsByUserId[$user->getId()->toRfc4122()] = $participant;
            $username = self::stripDiscordIdSuffix((string) $user->getUsername());
            if ($username !== '') {
                $byName[CraigInfoParser::speakerKey($username)] ??= $participant;
            }
        }

        $byDiscordId = [];
        if ($participantsByUserId === []) {
            return ['byDiscordId' => $byDiscordId, 'byName' => $byName];
        }

        $socialAccounts = $this->socialAccountRepository->findBy([
            'provider' => SocialAccountProvider::Discord,
            'user' => array_keys($participantsByUserId),
        ]);
        foreach ($socialAccounts as $socialAccount) {
            $participant = $this->participantOf($socialAccount, $participantsByUserId);
            if ($participant === null) {
                continue;
            }

            $byDiscordId[(string) $socialAccount->getProviderUserId()] = $participant;
            $discordName = CraigInfoParser::speakerKey((string) $socialAccount->getDisplayName());
            if ($discordName !== '') {
                $byName[$discordName] = $participant;
            }
        }

        return ['byDiscordId' => $byDiscordId, 'byName' => $byName];
    }

    /**
     * @param array<string, LarpParticipant> $participantsByUserId
     */
    private function participantOf(UserSocialAccount $socialAccount, array $participantsByUserId): ?LarpParticipant
    {
        $userId = $socialAccount->getUser()?->getId()->toRfc4122();

        return $userId === null ? null : $participantsByUserId[$userId] ?? null;
    }

    private function connectedGuildId(Larp $larp): ?string
    {
        $integration = $this->integrationRepository->findOneByLarpAndProvider($larp, LarpIntegrationProvider::Discord);
        if (!$integration instanceof LarpIntegration || !$integration->getStatus()->isUsable()) {
            return null;
        }

        return $integration->getExternalAccountId();
    }

    /**
     * @return array<string, list<string>>
     */
    private function searchGuildMembers(string $guildId, string $speaker): array
    {
        try {
            $members = $this->discordClient->searchGuildMembers($guildId, CraigInfoParser::speakerKey($speaker), self::MEMBER_SEARCH_LIMIT);
        } catch (DiscordApiException|InvalidArgumentException $failure) {
            $this->logger->info('Discord member search for a call speaker failed', ['exception' => $failure]);

            return [];
        }

        $namesByMemberId = [];
        foreach ($members as $member) {
            $namesByMemberId[$member->user->id] = array_map(CraigInfoParser::speakerKey(...), $member->names());
        }

        return $namesByMemberId;
    }

    public static function stripDiscordIdSuffix(string $username): string
    {
        return trim((string) preg_replace('/\s*\(\d+\)$/', '', $username));
    }
}
