<?php

declare(strict_types=1);

namespace Tests\Functional\CallTranscript;

use App\Domain\Account\Entity\User;
use App\Domain\Account\Entity\UserSocialAccount;
use App\Domain\CallTranscript\Entity\CallRecordingTrack;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Integrations\Entity\Enum\SocialAccountProvider;
use Tests\Support\CallTranscript\CallTranscriptFlow;
use Tests\Support\CallTranscript\CraigArchiveBuilder;
use Tests\Support\CallTranscript\FakeSpeechToTextProvider;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Integrations\LarpIntegrationFactory;
use Tests\Support\FunctionalTester;
use Tests\Support\Http\IntegrationFixtureRouters;

class CallTranscriptSpeakerMappingCest
{
    use CallTranscriptFlow;

    public function _before(FunctionalTester $I): void
    {
        FakeSpeechToTextProvider::reset();
    }

    public function speakersAreMappedToParticipantsAndCanBeChanged(FunctionalTester $I): void
    {
        $I->wantTo('see Craig speakers mapped to LARP participants, change one and get real names in the transcript');

        $organizer = UserFactory::new()->approved()->create(['username' => 'Olga Organizatorka'])->_real();
        $larp = LarpFactory::createDraftLarp($organizer);
        LarpIntegrationFactory::new()->discord()->botInstall(IntegrationFixtureRouters::DISCORD_GUILD_ID)->forLarp($larp)->create();

        $anna = $this->participantWithDiscord($I, $larp, 'Anna Nowak', '11', 'anna_renamed#0', 'player');
        $tomek = $this->participantWithDiscord($I, $larp, 'Tomasz Kowalski(1200000000000000077)', '1200000000000000077', 'tomasz.k#0', 'gameMaster');

        $I->amLoggedInAs($organizer);
        $this->upload($I, $larp->getId()->toRfc4122(), 'Who is who', [
            '1-anna.flac' => '1|Cześć',
            '2-tomek.flac' => '2|Hej',
            '3-ewa.flac' => '3|Siema',
            'info.txt' => CraigArchiveBuilder::info(),
        ]);

        $I->seeOptionIsSelected('[data-call-track="anna"] select', 'Anna Nowak (Player)');
        $I->seeOptionIsSelected('[data-call-track="tomek"] select', 'Tomasz Kowalski (Game Master)');
        $I->seeOptionIsSelected('[data-call-track="ewa"] select', 'Keep "ewa"');
        $tomekTrack = $I->grabEntityFromRepository(CallRecordingTrack::class, ['speaker' => 'tomek']);
        $I->assertSame('1200000000000000077', $tomekTrack->getDiscordUserId());

        $organizerParticipant = $I->grabEntityFromRepository(LarpParticipant::class, ['user' => $organizer, 'larp' => $larp->_real()]);
        $I->selectOption('[data-call-track="ewa"] select', $organizerParticipant->getId()->toRfc4122());
        $I->selectOption('[data-call-track="anna"] select', '');
        $this->startTranscription($I, 3);

        $document = $this->findRecording($I, 'Who is who')->getKnowledgeDocument();
        $I->assertStringContainsString(
            '<p>[00:00:01] anna: Cześć</p><p>[00:00:02] Tomasz Kowalski (Mistrz Gry): Hej</p><p>[00:00:03] Olga Organizatorka (Organizator): Siema</p>',
            (string) $document?->getContent(),
        );
        $I->assertNotNull($anna->getId());
        $I->assertNotNull($tomek->getId());
    }

    private function participantWithDiscord(FunctionalTester $I, mixed $larp, string $username, string $discordId, string $discordName, string $role): LarpParticipant
    {
        /** @var User $user */
        $user = UserFactory::new()->approved()->create(['username' => $username])->_real();
        $participant = LarpParticipantFactory::new()->forLarp($larp)->forUser($user)->{$role}()->create()->_real();

        $socialAccount = new UserSocialAccount();
        $socialAccount->setProvider(SocialAccountProvider::Discord);
        $socialAccount->setProviderUserId($discordId);
        $socialAccount->setDisplayName($discordName);
        $socialAccount->setUser($user);
        $I->getEntityManager()->persist($socialAccount);
        $I->getEntityManager()->flush();

        return $participant;
    }
}
