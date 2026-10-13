<?php

use App\Domain\Core\Service\Monitoring\BootErrorReporter;
use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    BootErrorReporter::register($context);

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
