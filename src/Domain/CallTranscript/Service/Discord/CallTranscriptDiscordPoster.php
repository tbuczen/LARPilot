<?php

namespace App\Domain\CallTranscript\Service\Discord;

use App\Domain\CallTranscript\Entity\CallRecording;
use App\Domain\CallTranscript\Service\CallTranscriptDestinations;
use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Discord\Client\DiscordClient;
use App\Domain\Integrations\Discord\Client\Exception\DiscordAccessLostException;
use App\Domain\Integrations\Discord\DiscordBotPermissions;
use App\Domain\Integrations\Entity\Enum\ExternalSourceKind;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Entity\LarpIntegration;
use RuntimeException;

final readonly class CallTranscriptDiscordPoster
{
    public function __construct(
        private CallTranscriptDestinations $destinations,
        private CallTranscriptDiscordMessages $messages,
        private DiscordClient $discordClient,
    ) {
    }

    public function post(CallRecording $recording): void
    {
        $larp = $recording->getLarp();
        $integration = $larp instanceof Larp ? $this->destinations->discordIntegration($larp) : null;
        if (!$larp instanceof Larp || !$integration instanceof LarpIntegration) {
            throw new RuntimeException('Discord is not connected to this LARP any more.');
        }

        $channel = $this->destinations->discordChannel($larp, $recording->getDiscordChannelId());
        if (!$channel instanceof ExternalSource) {
            throw new RuntimeException('The chosen Discord channel is no longer watched by this LARP.');
        }

        $messages = $this->messages->build($recording);
        if ($messages === []) {
            throw new RuntimeException('There is nothing to post.');
        }

        try {
            $this->postMessages($recording, $integration, $channel, $messages);
        } catch (DiscordAccessLostException $failure) {
            DiscordBotPermissions::markPostingDenied($integration);
            throw new RuntimeException('The LARPilot bot may not post in this channel. Click "Update bot permissions" on the Discord integration page, then retry.', 0, $failure);
        }
    }

    /**
     * @param non-empty-list<string> $messages
     */
    private function postMessages(CallRecording $recording, LarpIntegration $integration, ExternalSource $channel, array $messages): void
    {
        $threadId = $recording->getDiscordThreadId();
        if ($threadId === null) {
            $thread = $channel->getKind() === ExternalSourceKind::DiscordForum
                ? $this->discordClient->startForumPost($channel->getExternalId(), $recording->getTitle(), array_shift($messages))
                : $this->discordClient->startPublicThread($channel->getExternalId(), $recording->getTitle());
            $threadId = $thread->id;
            $recording->startDiscordThread($threadId, sprintf('https://discord.com/channels/%s/%s', $integration->getExternalAccountId(), $threadId));
        }

        foreach ($messages as $message) {
            $this->discordClient->postMessage($threadId, $message);
        }
    }
}
