<?php

namespace App\Domain\Integrations\Discord;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\Dto\DiscordBotAuthorization;
use App\Domain\Integrations\Discord\Client\Dto\DiscordChannel;
use App\Domain\Integrations\Discord\Client\Dto\DiscordGuild;
use App\Domain\Integrations\Discord\Client\Dto\DiscordThread;
use App\Domain\Integrations\Discord\Client\Exception\DiscordAccessLostException;
use App\Domain\Integrations\Discord\Client\Exception\DiscordApiException;
use App\Domain\Integrations\Discord\Picker\DiscordChannelPicker;
use App\Domain\Integrations\Discord\Sync\DiscordSourceSync;
use App\Domain\Integrations\DTO\ConnectionCallback;
use App\Domain\Integrations\DTO\IntegrationHealth;
use App\Domain\Integrations\DTO\RemoteItem;
use App\Domain\Integrations\DTO\RemoteSource;
use App\Domain\Integrations\DTO\SourceSyncPage;
use App\Domain\Integrations\Entity\Enum\ConnectionType;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use App\Domain\Integrations\Repository\ExternalReferenceRepository;
use App\Domain\Integrations\Repository\ExternalSourceRepository;
use App\Domain\Integrations\Repository\LarpIntegrationRepository;
use App\Domain\Integrations\Service\Capability\ConnectableIntegrationInterface;
use App\Domain\Integrations\Service\Capability\ConversationSourceIntegrationInterface;
use App\Domain\Integrations\Service\Capability\CredentialProviderInterface;
use App\Domain\Integrations\Service\Capability\SourceSyncIntegrationInterface;
use App\Domain\Integrations\Service\Credential\BotCredentialProvider;
use App\Domain\Integrations\Service\Exceptions\IntegrationConnectionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class DiscordIntegrationProvider implements ConnectableIntegrationInterface, CredentialProviderInterface, SourceSyncIntegrationInterface, ConversationSourceIntegrationInterface
{
    public const AUTHORIZE_URL = 'https://discord.com/oauth2/authorize';
    public const BOT_PERMISSIONS = '309237713920';
    public const GUILD_INSTALL = '0';
    public const GUILD_NAME_CONFIG_KEY = 'guildName';
    public const ACCOUNT_LABEL_CONFIG_KEY = 'accountLabel';
    public const MAX_LISTED_ARCHIVED_THREADS = 100;

    public function __construct(
        private BotCredentialProvider $botCredentialProvider,
        private DiscordClient $discordClient,
        private LarpIntegrationRepository $larpIntegrationRepository,
        private ExternalSourceRepository $externalSourceRepository,
        private ExternalReferenceRepository $externalReferenceRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        #[Autowire('%env(DISCORD_CLIENT_ID)%')]
        private string $discordClientId,
        #[Autowire('%env(DISCORD_CLIENT_SECRET)%')]
        private string $discordClientSecret,
        private DiscordChannelPicker $discordChannelPicker,
        private DiscordSourceSync $discordSourceSync,
    ) {
    }

    public static function providerKey(): string
    {
        return LarpIntegrationProvider::Discord->value;
    }

    public function authorizationHeader(LarpIntegration $integration): string
    {
        return $this->botCredentialProvider->authorizationHeader($integration);
    }

    public function authorizationUrl(Larp $larp, string $state, string $redirectUri): string
    {
        return self::AUTHORIZE_URL . '?' . http_build_query([
            'client_id' => $this->discordClientId,
            'scope' => 'bot',
            'permissions' => self::BOT_PERMISSIONS,
            'integration_type' => self::GUILD_INSTALL,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function completeConnection(Larp $larp, ConnectionCallback $callback, User $connectingUser): LarpIntegration
    {
        $authorizationCode = $callback->authorizationCode();
        if ($authorizationCode === null) {
            throw new IntegrationConnectionFailedException('Discord did not return an authorization code.');
        }

        $guildId = $this->authorizedGuildId($authorizationCode, $callback);
        $guild = $this->verifiedGuild($guildId);

        $integration = $this->larpIntegrationRepository->findOneByLarpAndProvider($larp, LarpIntegrationProvider::Discord);
        if (!$integration instanceof LarpIntegration) {
            $integration = new LarpIntegration();
            $integration->setCreatedBy($connectingUser);
            $integration->setLarp($larp);
            $integration->setProvider(LarpIntegrationProvider::Discord);
        }

        $previousGuildId = $integration->getExternalAccountId();
        if ($previousGuildId !== null && $previousGuildId !== $guild->id) {
            $this->purgeLarpData($integration);
        }

        $integration->setConnectionType(ConnectionType::BotInstall);
        $integration->setAccessToken(null);
        $integration->setRefreshToken(null);
        $integration->setExpiresAt(null);
        $integration->setScopes('bot');
        $integration->setExternalAccountId($guild->id);
        $integration->setOwner($guild->name);
        $integration->setConfig(array_merge($integration->getConfig() ?? [], [
            self::GUILD_NAME_CONFIG_KEY => $guild->name,
            self::ACCOUNT_LABEL_CONFIG_KEY => $guild->name,
            DiscordBotPermissions::GRANTED_CONFIG_KEY => DiscordBotPermissions::grantedFromCallback($callback->query['permissions'] ?? null),
            DiscordBotPermissions::POSTING_DENIED_CONFIG_KEY => false,
        ]));
        $integration->markActive();
        $this->larpIntegrationRepository->save($integration);

        if ($previousGuildId !== null && $previousGuildId !== $guild->id) {
            $this->leaveGuildIfUnused($integration, $previousGuildId);
        }

        return $integration;
    }

    public function disconnect(LarpIntegration $integration): void
    {
        $guildId = $integration->getExternalAccountId();
        $guildStillUsed = $guildId === null || $this->isGuildUsedElsewhere($integration, $guildId);

        $this->entityManager->wrapInTransaction(function () use ($integration): void {
            $this->purgeLarpData($integration);
            $this->entityManager->remove($integration);
        });

        if ($guildStillUsed) {
            return;
        }

        $this->leaveGuild($guildId);
    }

    public function health(LarpIntegration $integration): IntegrationHealth
    {
        $guildId = $integration->getExternalAccountId();
        if ($guildId === null) {
            return IntegrationHealth::needsReauth('Discord server is unknown.');
        }

        try {
            $this->discordClient->getGuild($guildId);
        } catch (DiscordAccessLostException $exception) {
            return IntegrationHealth::needsReauth($exception->getMessage());
        }

        return IntegrationHealth::healthy();
    }

    public function fetchSourcePage(ExternalSource $source, ?array $cursor): SourceSyncPage
    {
        return $this->discordSourceSync->fetchSourcePage($source, $cursor);
    }

    public function listChannels(LarpIntegration $integration): array
    {
        return array_map(
            static fn (DiscordChannel $channel): RemoteSource => new RemoteSource(
                $channel->id,
                $channel->name ?? $channel->id,
                DiscordSourceSync::threadUrl($integration->getExternalAccountId() ?? '', $channel->id),
                $channel->parentId,
            ),
            $this->discordChannelPicker->pickableChannels($integration),
        );
    }

    public function listThreads(LarpIntegration $integration, string $channelId): array
    {
        $guildId = $integration->getExternalAccountId();
        if ($guildId === null) {
            return [];
        }

        $threads = [];
        foreach ($this->discordClient->listActiveThreads($guildId) as $activeThread) {
            if (!DiscordSourceSync::isWatchedThread($activeThread, $channelId)) {
                continue;
            }

            $threads[$activeThread->id] = $activeThread;
        }

        $listedArchivedThreads = 0;
        foreach ($this->discordClient->listArchivedPublicThreads($channelId) as $archivedThread) {
            if (DiscordSourceSync::isWatchedThread($archivedThread, $channelId)) {
                $threads[$archivedThread->id] ??= $archivedThread;
            }

            if (++$listedArchivedThreads >= self::MAX_LISTED_ARCHIVED_THREADS) {
                break;
            }
        }

        return array_values(array_map(
            static fn (DiscordThread $thread): RemoteItem => new RemoteItem(
                $thread->id,
                $thread->name ?? $thread->id,
                DiscordSourceSync::threadUrl($guildId, $thread->id),
                $thread->archiveTimestamp,
            ),
            $threads,
        ));
    }

    private function authorizedGuildId(string $authorizationCode, ConnectionCallback $callback): string
    {
        try {
            $authorization = $this->discordClient->exchangeBotAuthorizationCode(
                $authorizationCode,
                $callback->redirectUri,
                $this->discordClientId,
                $this->discordClientSecret,
            );
        } catch (DiscordApiException $exception) {
            throw new IntegrationConnectionFailedException('Discord rejected the bot authorization code.', 0, $exception);
        }

        $this->revokeAuthorization($authorization);

        if ($authorization->guildId === null) {
            throw new IntegrationConnectionFailedException('Discord did not report which server the bot was added to.');
        }

        $guildHint = $callback->query['guild_id'] ?? null;
        if (is_string($guildHint) && $guildHint !== '' && $guildHint !== $authorization->guildId) {
            throw new IntegrationConnectionFailedException('Discord server in the redirect does not match the authorized server.');
        }

        return $authorization->guildId;
    }

    private function revokeAuthorization(DiscordBotAuthorization $authorization): void
    {
        if ($authorization->accessToken === '') {
            return;
        }

        try {
            $this->discordClient->revokeAccessToken($authorization->accessToken, $this->discordClientId, $this->discordClientSecret);
        } catch (DiscordApiException $exception) {
            $this->logger->warning('Could not revoke the Discord bot authorization token.', ['exception' => $exception]);
        }
    }

    private function verifiedGuild(string $guildId): DiscordGuild
    {
        try {
            return $this->discordClient->getGuild($guildId);
        } catch (DiscordApiException|\InvalidArgumentException $exception) {
            throw new IntegrationConnectionFailedException('The LARPilot bot cannot see the authorized Discord server.', 0, $exception);
        }
    }

    private function purgeLarpData(LarpIntegration $integration): void
    {
        $this->externalReferenceRepository->clearExcerptsForLarp($integration->getLarp(), LarpIntegrationProvider::Discord);
        $this->externalSourceRepository->deleteForIntegration($integration);
    }

    private function leaveGuildIfUnused(LarpIntegration $integration, string $guildId): void
    {
        if ($this->isGuildUsedElsewhere($integration, $guildId)) {
            return;
        }

        $this->leaveGuild($guildId);
    }

    private function isGuildUsedElsewhere(LarpIntegration $integration, string $guildId): bool
    {
        return $this->larpIntegrationRepository->countOtherInstallsOfExternalAccount($integration, $guildId) > 0;
    }

    private function leaveGuild(string $guildId): void
    {
        try {
            $this->discordClient->leaveGuild($guildId);
        } catch (DiscordApiException|\InvalidArgumentException $exception) {
            $this->logger->warning('The LARPilot bot could not leave the Discord server.', ['guildId' => $guildId, 'exception' => $exception]);
        }
    }
}
