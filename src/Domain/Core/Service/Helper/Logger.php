<?php

namespace App\Domain\Core\Service\Helper;

use Psr\Log\LoggerInterface;

class Logger
{
    private static LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public static function get(): LoggerInterface
    {
        return self::$logger;
    }
}
