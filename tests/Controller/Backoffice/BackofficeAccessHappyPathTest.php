<?php

declare(strict_types=1);

namespace App\Tests\Controller\Backoffice;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User; // adjust to your User entity FQCN
use App\Entity\Larp; // adjust to your Larp entity FQCN
use Doctrine\ORM\EntityManagerInterface;

final class BackofficeAccessHappyPathTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Ensure we have a LARP and a super-admin for it
        $larp = $this->provideLarp();
        $user = $this->provideSuperAdminForLarp($larp);

        // Symfony 6+: loginUser available via BrowserKit
        $this->client->loginUser($user);
    }

    public function test_super_admin_can_access_all_backoffice_pages(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);

        $routes = $router->getRouteCollection();
        $backofficePaths = $this->collectBackofficePaths($routes);

        self::assertNotEmpty($backofficePaths, 'No backoffice routes discovered. Adjust the filters in collectBackofficePaths().');

        foreach ($backofficePaths as $path) {
            // GET only, ignore routes with unresolved placeholders
            if ($this->pathHasPlaceholders($path)) {
                continue;
            }

            $this->client->request('GET', $path);
            $status = $this->client->getResponse()->getStatusCode();

            // Accept 200 OK. If a route intentionally redirects to a default child, also accept 302.
            $isOk = $status === Response::HTTP_OK || $status === Response::HTTP_FOUND;
            $contentType = $this->client->getResponse()->headers->get('content-type');

            self::assertTrue(
                $isOk,
                sprintf('Expected 200/302 for "%s", got %d. Content-Type: %s', $path, $status, (string) $contentType)
            );
        }
    }

    /**
     * Change filters here if your backoffice is under a different prefix or namespace.
     */
    private function collectBackofficePaths(RouteCollection $routes): array
    {
        $paths = [];

        /** @var Route $route */
        foreach ($routes as $name => $route) {
            // Filter 1: path prefix
            $path = $route->getPath();
            if (!is_string($path)) {
                continue;
            }

            $isBackofficePath = str_starts_with($path, '/backoffice')
                || str_starts_with($path, '/admin'); // add/adjust as needed

            // Filter 2: controller namespace (safer if paths vary)
            $defaults = $route->getDefaults();
            $controller = $defaults['_controller'] ?? null;
            $isBackofficeController = is_string($controller) && str_contains($controller, 'Controller\\Backoffice\\');

            if (!$isBackofficePath && !$isBackofficeController) {
                continue;
            }

            // Only GET
            $methods = $route->getMethods();
            if (!empty($methods) && !in_array('GET', $methods, true)) {
                continue;
            }

            $paths[] = $path;
        }

        // Unique and stable order
        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }

    private function pathHasPlaceholders(string $path): bool
    {
        // Basic check for unresolved {param}, skip those to keep test happy-path and env-agnostic
        return (bool) preg_match('/\{[^}]+\}/', $path);
    }

    private function provideLarp(): Larp
    {
        // Try to reuse an existing test LARP to speed up runs
        $repo = $this->em->getRepository(Larp::class);
        $larp = $repo->findOneBy(['slug' => 'test-larp']);

        if (!$larp instanceof \App\Entity\Larp) {
            $larp = new Larp();
            // Set minimum viable fields; adjust to your entity
            $larp->setTitle('Test LARP');
            if (method_exists($larp, 'setSlug')) {
                $larp->setSlug('test-larp');
            }
            $this->em->persist($larp);
            $this->em->flush();
        }

        return $larp;
    }

    private function provideSuperAdminForLarp(Larp $larp): User
    {
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => 'superadmin+test@example.com']);

        if (!$user instanceof \App\Entity\User) {
            $user = new User();
            // Set fields as per your User entity
            if (method_exists($user, 'setEmail')) {
                $user->setEmail('superadmin+test@example.com');
            }
            if (method_exists($user, 'setPassword')) {
                // Password irrelevant when using loginUser(); still set a placeholder
                $user->setPassword('!not-used-in-tests!');
            }
            if (method_exists($user, 'setRoles')) {
                $roles = $user->getRoles();
                $roles[] = 'ROLE_SUPER_ADMIN';
                $user->setRoles(array_values(array_unique($roles)));
            }
            $this->em->persist($user);
            $this->em->flush();
        }

        // Ensure association as "super admin of this LARP"
        // Adjust based on your domain model: many-to-many, pivot, or dedicated relation
        if (method_exists($user, 'addAdministratedLarp')) {
            $user->addAdministratedLarp($larp);
            $this->em->flush();
        } elseif (method_exists($user, 'setLarp') && null === $user->getLarp()) {
            $user->setLarp($larp);
            $this->em->flush();
        }
        // If you have a specific permission entity, create and persist here.

        return $user;
    }
}
