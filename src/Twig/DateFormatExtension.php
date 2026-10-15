<?php

declare(strict_types=1);

namespace App\Twig;

use App\Domain\Core\Service\Helper\DateFormat;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;

final class DateFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('display_date', $this->displayDate(...), ['needs_environment' => true]),
            new TwigFilter('display_datetime', $this->displayDateTime(...), ['needs_environment' => true]),
            new TwigFilter('display_time', $this->displayTime(...), ['needs_environment' => true]),
        ];
    }

    public function displayDate(Environment $environment, \DateTimeInterface|string|int|null $date, ?string $timezone = null): string
    {
        return $environment->getExtension(CoreExtension::class)->formatDate($date, DateFormat::DATE, $timezone);
    }

    public function displayDateTime(Environment $environment, \DateTimeInterface|string|int|null $date, ?string $timezone = null): string
    {
        return $environment->getExtension(CoreExtension::class)->formatDate($date, DateFormat::DATETIME, $timezone);
    }

    public function displayTime(Environment $environment, \DateTimeInterface|string|int|null $date, ?string $timezone = null): string
    {
        return $environment->getExtension(CoreExtension::class)->formatDate($date, DateFormat::TIME, $timezone);
    }
}
