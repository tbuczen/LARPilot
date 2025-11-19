<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Enum\LarpStageStatus;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Location;
use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\Symfony;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Support\Factory\Account\UserFactory;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\Factory\Core\LocationFactory;

/**
 * Authentication Helper for Codeception Tests
 *
 * Integrates Foundry factories with Codeception actors:
 * - Session-based authentication via amLoggedInAs()
 * - User creation using UserFactory
 * - LARP/Location creation using domain factories
 * - Route generation and service access through Symfony module
 */
class Authentication extends Module
{
    protected ?Symfony $symfony = null;

    /**
     * @throws ModuleException
     */
    public function _beforeSuite($settings = []): void
    {
        /** @var Symfony $module */
        $module = $this->getModule('Symfony');
        $this->symfony = $module;
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
        /** @var EntityManagerInterface $service */
        $service = $this->symfony->grabService(EntityManagerInterface::class);
        return $service;
    }

    /**
     * Generate URL from route name (convenience method)
     */
    public function getUrl(string $route, array $parameters = []): string
    {
        return $this->symfony->grabService('router')->generate($route, $parameters);
    }

    public function createSuperAdmin(): User
    {
        return UserFactory::new()->approved()->superAdmin()->create()->_real();
    }
}
