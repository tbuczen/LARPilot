<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use App\Domain\Account\Entity\User;
use Codeception\Module;
use Codeception\Module\Symfony;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Authentication Helper for Codeception Tests
 *
 * Follows Single Responsibility Principle:
 * - Only handles session-based authentication for Codeception tests
 * - User creation delegated to Foundry factories
 * - URL generation and service access through parent Symfony module
 */
class Authentication extends Module
{
    protected ?Symfony $symfony = null;

    public function _beforeSuite($settings = []): void
    {
        $this->symfony = $this->getModule('Symfony');
    }

    /**
     * Log in as a specific user by creating a session token
     * This is the core authentication functionality for Codeception
     */
    public function amLoggedInAs(User $user, string $firewall = 'main'): void
    {
        $client = $this->symfony->client;
        $session = $client->getContainer()->get('session.factory')->createSession();
        $token = new UsernamePasswordToken($user, $firewall, $user->getRoles());
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }

    /**
     * Get EntityManager instance
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->symfony->grabService(EntityManagerInterface::class);
    }

    /**
     * Generate URL from route name (convenience method)
     */
    public function getUrl(string $route, array $parameters = []): string
    {
        return $this->symfony->grabService('router')->generate($route, $parameters);
    }
}
