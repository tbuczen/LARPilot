<?php

declare(strict_types=1);

namespace Tests\Functional\Account;

use App\Domain\Core\Entity\Enum\LarpStageStatus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Tests\Support\Factory\Core\LarpFactory;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Tests\Support\FunctionalTester;

class BackofficeAccessHappyPathCest
{


    public function _before(FunctionalTester $I): void
    {
        $user = $I->createSuperAdmin();

        $larp = LarpFactory::new()
            ->withStatus(LarpStageStatus::DRAFT)
            ->withCreator($user)
            ->create();

        LarpParticipantFactory::new()
            ->organizer()
            ->forLarp($larp)
            ->forUser($user)
            ->create();

        $I->amLoggedInAs($user);
    }

    public function superAdminCanAccessAllBackofficePages(FunctionalTester $I): void
    {
        $I->wantTo('verify that super admin can access all backoffice pages');

        $router = $I->grabService(RouterInterface::class);
        $routes = $router->getRouteCollection();
        $backofficePaths = $this->collectBackofficePaths($routes);

        $I->assertNotEmpty(
            $backofficePaths,
            'No backoffice routes discovered. Adjust the filters in collectBackofficePaths().'
        );

        foreach ($backofficePaths as $path) {
            // Skip routes with unresolved placeholders
            if ($this->pathHasPlaceholders($path)) {
                continue;
            }

            $I->amOnPage($path);
//            $I->g
            $I->seeResponseCodeIsBetween(200,302);
        }
    }

    /**
     * Collect all backoffice paths from route collection
     *
     * @return array<string>
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
                || str_starts_with($path, '/admin');

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

    /**
     * Check if path has unresolved placeholders
     */
    private function pathHasPlaceholders(string $path): bool
    {
        return (bool) preg_match('/\{[^}]+\}/', $path);
    }
}
