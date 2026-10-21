<?php

use App\Domain\Core\Service\Monitoring\BootErrorReporter;
use App\Kernel;

$maintenanceFlag = dirname(__DIR__) . '/var/maintenance';
if (is_file($maintenanceFlag) && filemtime($maintenanceFlag) > time() - 600) {
    http_response_code(503);
    header('Retry-After: 30');
    header('Cache-Control: no-store');
    readfile(__DIR__ . '/maintenance.html');
    exit;
}

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    BootErrorReporter::register($context);

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
