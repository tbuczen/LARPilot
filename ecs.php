<?php

use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $config): void {
    $config->paths([__DIR__ . '/src']);
    $config->sets([
        Symplify\EasyCodingStandard\ValueObject\Set\SetList::PSR_12,
    ]);
};
