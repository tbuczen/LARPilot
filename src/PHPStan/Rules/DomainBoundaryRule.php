<?php

declare(strict_types=1);

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces domain boundary rules to prevent circular dependencies
 * and unauthorized cross-domain imports.
 *
 * Domain Dependency Map:
 * - Infrastructure: Can be imported by all domains (shared kernel)
 * - Account: Can be imported by Participant, Public
 * - Larp: Can be imported by most domains (central aggregate)
 * - StoryObject: Can be imported by Application, StoryMarketplace, EventPlanning, Integration
 * - Application: Depends on StoryObject, Larp, Participant
 * - Participant: Depends on Account, Larp
 * - StoryMarketplace: Depends on StoryObject
 * - EventPlanning: Depends on StoryObject, Map, Participant
 * - Integration: Depends on StoryObject
 * - Public: Depends on Account
 * - Map: Depends on Larp
 * - Kanban: Depends on Larp
 * - Incident: Depends on Larp
 *
 * @implements Rule<Node\Stmt\Use_>
 */
final class DomainBoundaryRule implements Rule
{
    /**
     * Allowed dependencies per domain.
     * Key: Domain name, Value: Array of allowed domain dependencies
     */
    private const DOMAIN_DEPENDENCIES = [
        'Infrastructure' => [], // Shared kernel, no dependencies on other domains
        'Core' => ['StoryObject', 'Infrastructure', 'Account'], // Shared kernel (legacy name, being migrated to Infrastructure)
        'Account' => ['Infrastructure', 'Core'],
        'Public' => ['Infrastructure', 'Core', 'Account', 'Larp'],
        'Larp' => ['Infrastructure', 'Core', 'Account'],
        'StoryObject' => ['Infrastructure', 'Core', 'Larp', 'Integrations', 'Account', 'StoryMarketplace'],
        'Application' => ['Infrastructure', 'Core', 'Larp', 'StoryObject', 'Participant', 'Account'],
        'Participant' => ['Infrastructure', 'Core', 'Account', 'Larp'],
        'StoryMarketplace' => ['Infrastructure', 'Core', 'Larp', 'StoryObject', 'Account'],
        'Kanban' => ['Infrastructure', 'Core', 'Larp'],
        'Incident' => ['Infrastructure', 'Core', 'Larp'],
        'Incidents' => ['Infrastructure', 'Core', 'Larp'],
        'Map' => ['Infrastructure', 'Core', 'Larp'],
        'EventPlanning' => ['Infrastructure', 'Core', 'Larp', 'StoryObject', 'Map', 'Participant'],
        'Integration' => ['Infrastructure', 'Core', 'Larp', 'StoryObject'],
        'Integrations' => ['Infrastructure', 'Core', 'Larp', 'StoryObject'],
        'Feedback' => ['Infrastructure', 'Core'],
        'Gallery' => ['Infrastructure', 'Core', 'Larp', 'Account'],
    ];

    public function getNodeType(): string
    {
        return Node\Stmt\Use_::class;
    }

    /**
     * @param Node\Stmt\Use_ $node
     * @return array<PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $currentFile = $scope->getFile();

        // Only check files in src/Domain/
        if (!str_contains($currentFile, '/src/Domain/')) {
            return [];
        }

        // Extract current domain from file path
        $currentDomain = $this->extractDomainFromPath($currentFile);
        if ($currentDomain === null) {
            return [];
        }

        $errors = [];

        foreach ($node->uses as $use) {
            $importedClass = $use->name->toString();

            // Skip non-domain imports (Symfony, Doctrine, etc.)
            if (!str_starts_with($importedClass, 'App\\Domain\\')) {
                continue;
            }

            // Extract imported domain
            $importedDomain = $this->extractDomainFromClass($importedClass);
            if ($importedDomain === null || $importedDomain === $currentDomain) {
                continue; // Allow same-domain imports
            }

            // Check if dependency is allowed
            $allowedDependencies = self::DOMAIN_DEPENDENCIES[$currentDomain] ?? [];

            if (!in_array($importedDomain, $allowedDependencies, true)) {
                //                dd($currentDomain , $allowedDependencies, $importedDomain);
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Domain boundary violation: %s domain cannot import from %s domain. Allowed: %s',
                    $currentDomain,
                    $importedDomain,
                    implode(', ', $allowedDependencies)
                ))
                    ->identifier('domain.boundaryViolation')
                    ->build();
            }
        }

        return $errors;
    }

    private function extractDomainFromPath(string $filePath): ?string
    {
        if (preg_match('#/src/Domain/([^/]+)/#', $filePath, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractDomainFromClass(string $className): ?string
    {
        if (preg_match('#^App\\\\Domain\\\\([^\\\\]+)\\\\#', $className, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
