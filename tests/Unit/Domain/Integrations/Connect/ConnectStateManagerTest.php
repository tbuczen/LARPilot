<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Connect;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Service\Connect\ConnectStateManager;
use App\Domain\Integrations\Service\Exceptions\InvalidConnectStateException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ConnectStateManagerTest extends TestCase
{
    private const SECRET = 'connect-state-test-secret';

    public function testIssuedStateResolvesToItsLarpOnce(): void
    {
        $connectStateManager = $this->createConnectStateManager($this->createRequestStack());
        $larp = new Larp();
        $state = $connectStateManager->issue($larp, LarpIntegrationProvider::Google);

        $this->assertSame($larp->getId()->toRfc4122(), $connectStateManager->consume($state, LarpIntegrationProvider::Google));

        $this->expectException(InvalidConnectStateException::class);
        $connectStateManager->consume($state, LarpIntegrationProvider::Google);
    }

    public function testStatesOfTwoTabsDoNotOverwriteEachOther(): void
    {
        $connectStateManager = $this->createConnectStateManager($this->createRequestStack());
        $firstLarp = new Larp();
        $secondLarp = new Larp();
        $firstState = $connectStateManager->issue($firstLarp, LarpIntegrationProvider::Google);
        $secondState = $connectStateManager->issue($secondLarp, LarpIntegrationProvider::Google);

        $this->assertSame($secondLarp->getId()->toRfc4122(), $connectStateManager->consume($secondState, LarpIntegrationProvider::Google));
        $this->assertSame($firstLarp->getId()->toRfc4122(), $connectStateManager->consume($firstState, LarpIntegrationProvider::Google));
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $connectStateManager = $this->createConnectStateManager($this->createRequestStack());
        $state = $connectStateManager->issue(new Larp(), LarpIntegrationProvider::Google);
        [$payload, $signature] = explode('.', $state);
        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['larp'] = (new Larp())->getId()->toRfc4122();
        $forgedPayload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->expectException(InvalidConnectStateException::class);
        $connectStateManager->consume($forgedPayload . '.' . $signature, LarpIntegrationProvider::Google);
    }

    public function testStateSignedWithAnotherSecretIsRejected(): void
    {
        $requestStack = $this->createRequestStack();
        $foreignState = (new ConnectStateManager($requestStack, 'another-secret'))->issue(new Larp(), LarpIntegrationProvider::Google);

        $this->expectException(InvalidConnectStateException::class);
        $this->createConnectStateManager($requestStack)->consume($foreignState, LarpIntegrationProvider::Google);
    }

    public function testStateFromAnotherSessionIsRejected(): void
    {
        $state = $this->createConnectStateManager($this->createRequestStack())->issue(new Larp(), LarpIntegrationProvider::Google);

        $this->expectException(InvalidConnectStateException::class);
        $this->createConnectStateManager($this->createRequestStack())->consume($state, LarpIntegrationProvider::Google);
    }

    public function testStateIssuedForAnotherProviderIsRejected(): void
    {
        $connectStateManager = $this->createConnectStateManager($this->createRequestStack());
        $state = $connectStateManager->issue(new Larp(), LarpIntegrationProvider::Miro);

        $this->expectException(InvalidConnectStateException::class);
        $connectStateManager->consume($state, LarpIntegrationProvider::Google);
    }

    public function testMalformedStateIsRejected(): void
    {
        $this->expectException(InvalidConnectStateException::class);
        $this->createConnectStateManager($this->createRequestStack())->consume('not-a-state', LarpIntegrationProvider::Google);
    }

    private function createConnectStateManager(RequestStack $requestStack): ConnectStateManager
    {
        return new ConnectStateManager($requestStack, self::SECRET);
    }

    private function createRequestStack(): RequestStack
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
