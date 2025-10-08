<?php
// PHP
declare(strict_types=1);

use Rector\CodingStyle\ClassNameImport\ClassNameImportSkipVoter\FullyQualifiedNameClassNameImportSkipVoter;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\TwigSetList;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Skip vendor and generated dirs
    $rectorConfig->skip([
        __DIR__ . '/var',
        __DIR__ . '/vendor',
    ]);

    // Target PHP 8.2
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        // Optional extra improvements
        TwigSetList::TWIG_UNDERSCORE_TO_NAMESPACE,
    ]);

    // Add some focused rules (safe refactors)
    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class, // promote trivial props
        SimplifyIfElseToTernaryRector::class,
    ]);

    $rectorConfig->importShortClasses(true);

    $rectorConfig->autoloadPaths([__DIR__ . '/vendor/autoload.php']);
};
