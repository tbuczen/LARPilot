<?php

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAliasTagFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $config): void {
    $config->paths([__DIR__ . '/src', __DIR__ . '/tests']);

    // Use PSR-12 as base
    $config->sets([
        Symplify\EasyCodingStandard\ValueObject\Set\SetList::PSR_12,
    ]);

    // Import-related rules
    $config->rules([
        // Remove unused imports
        NoUnusedImportsFixer::class,

        // Sort imports alphabetically
        OrderedImportsFixer::class,

        // Ensure blank line after namespace
        BlankLineAfterNamespaceFixer::class,

        // Import classes instead of using FQDN in PHPDoc
        PhpdocNoAliasTagFixer::class,
        PhpdocScalarFixer::class,

        // Import classes instead of using FQDN in code (PHP 7.4+)
        FullyQualifiedStrictTypesFixer::class,
    ]);

    // Configure ordered imports
    $config->ruleWithConfiguration(OrderedImportsFixer::class, [
        'sort_algorithm' => 'alpha',
        'imports_order' => ['class', 'function', 'const'],
    ]);
};
