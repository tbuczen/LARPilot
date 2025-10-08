<?php

namespace App;

use App\Helper\Logger;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();
        // Ensure logger is available before any other logic would use it
        $this->getContainer()->get(Logger::class);
    }
}
