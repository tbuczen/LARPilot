<?php

namespace App\Domain\Integrations\Service\Connect;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Entity\Enum\LarpIntegrationProvider;
use App\Domain\Integrations\Service\Exceptions\InvalidConnectStateException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class ConnectStateManager
{
    private const SESSION_KEY = 'integration_connect_pending_states';
    private const STATE_TTL_SECONDS = 900;
    private const SIGNATURE_ALGORITHM = 'sha256';

    public function __construct(
        private RequestStack $requestStack,
        #[Autowire('%kernel.secret%')]
        private string $secret,
    ) {
    }

    public function issue(Larp $larp, LarpIntegrationProvider $provider): string
    {
        $nonce = bin2hex(random_bytes(16));
        $expiresAt = time() + self::STATE_TTL_SECONDS;
        $payload = $this->base64UrlEncode(json_encode([
            'nonce' => $nonce,
            'larp' => $larp->getId()->toRfc4122(),
            'provider' => $provider->value,
            'expiresAt' => $expiresAt,
        ], JSON_THROW_ON_ERROR));

        $session = $this->session();
        $pendingStates = $this->unexpiredPendingStates($session);
        $pendingStates[$nonce] = $expiresAt;
        $session->set(self::SESSION_KEY, $pendingStates);

        return $payload . '.' . $this->sign($payload);
    }

    /**
     * @throws InvalidConnectStateException
     */
    public function consume(string $state, LarpIntegrationProvider $provider): string
    {
        $stateParts = explode('.', $state);
        if (count($stateParts) !== 2) {
            throw new InvalidConnectStateException('Malformed connect state.');
        }

        [$payload, $signature] = $stateParts;
        if (!hash_equals($this->sign($payload), $signature)) {
            throw new InvalidConnectStateException('Connect state signature mismatch.');
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($claims) || !isset($claims['nonce'], $claims['larp'], $claims['provider'], $claims['expiresAt'])) {
            throw new InvalidConnectStateException('Connect state payload is incomplete.');
        }

        if ($claims['provider'] !== $provider->value) {
            throw new InvalidConnectStateException('Connect state was issued for another provider.');
        }

        $session = $this->session();
        $pendingStates = $this->unexpiredPendingStates($session);
        $nonce = (string) $claims['nonce'];
        $isPending = array_key_exists($nonce, $pendingStates);
        unset($pendingStates[$nonce]);
        $session->set(self::SESSION_KEY, $pendingStates);

        if (!$isPending) {
            throw new InvalidConnectStateException('Connect state was already used or never issued in this session.');
        }

        if ((int) $claims['expiresAt'] < time()) {
            throw new InvalidConnectStateException('Connect state has expired.');
        }

        return (string) $claims['larp'];
    }

    /**
     * @return array<string, int>
     */
    private function unexpiredPendingStates(SessionInterface $session): array
    {
        $pendingStates = $session->get(self::SESSION_KEY, []);
        if (!is_array($pendingStates)) {
            return [];
        }

        $now = time();

        return array_filter($pendingStates, static fn (mixed $expiresAt): bool => is_int($expiresAt) && $expiresAt >= $now);
    }

    private function session(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    private function sign(string $payload): string
    {
        return $this->base64UrlEncode(hash_hmac(self::SIGNATURE_ALGORITHM, 'integration_connect_state|' . $payload, $this->secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
