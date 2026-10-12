<?php

declare(strict_types=1);

namespace Tests\Support\Http;

use Symfony\Component\HttpClient\Response\MockResponse;

final class FixtureRouter
{
    /**
     * @var array<string, array{method: string, pattern: string, query: array<string, mixed>, fixture: string, status: int, headers: array<string, string>}>
     */
    private array $routes = [];

    /**
     * @var list<array{method: string, path: string, query: array<string, mixed>}>
     */
    private array $requests = [];

    public function __construct(
        private readonly string $fixtureDirectory,
        private readonly string $basePath = '',
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function on(string $method, string $route, string $fixture, int $status = 200, array $headers = []): self
    {
        $method = strtoupper($method);
        $routePath = (string) parse_url($route, PHP_URL_PATH);
        parse_str((string) parse_url($route, PHP_URL_QUERY), $routeQuery);

        $this->routes[$method . ' ' . $route] = [
            'method' => $method,
            'pattern' => $this->compilePattern($routePath),
            'query' => $routeQuery,
            'fixture' => $fixture,
            'status' => $status,
            'headers' => $headers,
        ];

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(string $method, string $url, array $options = []): MockResponse
    {
        $method = strtoupper($method);
        $path = $this->relativePath(rawurldecode((string) parse_url($url, PHP_URL_PATH)));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->requests[] = ['method' => $method, 'path' => $path, 'query' => $query];

        $route = $this->match($method, $path, $query);
        if ($route === null) {
            throw new \LogicException(sprintf('No fixture route registered for %s %s', $method, $path));
        }

        return new MockResponse($this->fixture($route['fixture']), [
            'http_code' => $route['status'],
            'response_headers' => array_merge(['content-type' => 'application/json'], $route['headers']),
        ]);
    }

    public function fixture(string $fixture): string
    {
        $fixturePath = $this->fixtureDirectory . '/' . $fixture;
        if (!is_file($fixturePath)) {
            throw new \LogicException(sprintf('Fixture file "%s" does not exist', $fixturePath));
        }

        return (string) file_get_contents($fixturePath);
    }

    /**
     * @return array<mixed>
     */
    public function decodedFixture(string $fixture): array
    {
        return json_decode($this->fixture($fixture), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<array{method: string, path: string, query: array<string, mixed>}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{method: string, pattern: string, query: array<string, mixed>, fixture: string, status: int, headers: array<string, string>}|null
     */
    private function match(string $method, string $path, array $query): ?array
    {
        $bestRoute = null;
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || preg_match($route['pattern'], $path) !== 1) {
                continue;
            }
            if (array_intersect_assoc($route['query'], $query) !== $route['query']) {
                continue;
            }
            if ($bestRoute === null || count($route['query']) > count($bestRoute['query'])) {
                $bestRoute = $route;
            }
        }

        return $bestRoute;
    }

    private function relativePath(string $path): string
    {
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }

        return '/' . ltrim($path, '/');
    }

    private function compilePattern(string $routePath): string
    {
        $segments = preg_split('/(\{[^}\/]+\})/', $routePath, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $pattern = '';
        foreach ($segments as $segment) {
            $pattern .= preg_match('/^\{[^}\/]+\}$/', $segment) === 1 ? '[^\/]+' : preg_quote($segment, '#');
        }

        return '#^' . $pattern . '$#';
    }
}
