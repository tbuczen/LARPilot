<?php

declare(strict_types=1);

namespace Tests\Unit\CallTranscript;

use App\Domain\CallTranscript\Service\Discord\CallTranscriptDiscordMessages;
use App\Domain\Integrations\Discord\DiscordBotPermissions;
use App\Domain\Integrations\Discord\DiscordIntegrationProvider;
use App\Domain\Integrations\Entity\LarpIntegration;
use PHPUnit\Framework\TestCase;

class CallTranscriptDiscordMessagesTest extends TestCase
{
    public function testLinesAreGroupedIntoMessagesUnderTheLimit(): void
    {
        $messages = CallTranscriptDiscordMessages::split(['**Summary**', str_repeat('a', 15), '', '- one', '- two'], 20);

        $this->assertSame(['**Summary**', str_repeat('a', 15), '- one' . "\n" . '- two'], $messages);
    }

    public function testLinesLongerThanTheLimitAreCut(): void
    {
        $messages = CallTranscriptDiscordMessages::split([str_repeat('ą', 45)], 20);

        $this->assertSame([str_repeat('ą', 20), str_repeat('ą', 20), str_repeat('ą', 5)], $messages);
    }

    public function testInviteAsksForReadAndPostPermissions(): void
    {
        $this->assertSame((string) DiscordBotPermissions::REQUESTED, DiscordIntegrationProvider::BOT_PERMISSIONS);
    }

    public function testPostingNeedsTheNewPermissions(): void
    {
        $integration = new LarpIntegration();

        $integration->setConfig([]);
        $this->assertFalse(DiscordBotPermissions::canPost($integration));

        $integration->setConfig([DiscordBotPermissions::GRANTED_CONFIG_KEY => '66560']);
        $this->assertFalse(DiscordBotPermissions::canPost($integration));

        $integration->setConfig([DiscordBotPermissions::GRANTED_CONFIG_KEY => DiscordIntegrationProvider::BOT_PERMISSIONS]);
        $this->assertTrue(DiscordBotPermissions::canPost($integration));

        $integration->setConfig([DiscordBotPermissions::GRANTED_CONFIG_KEY => '8']);
        $this->assertTrue(DiscordBotPermissions::canPost($integration));

        DiscordBotPermissions::markPostingDenied($integration);
        $this->assertFalse(DiscordBotPermissions::canPost($integration));
    }
}
