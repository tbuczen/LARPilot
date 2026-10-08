<?php

declare(strict_types=1);

namespace App\Domain\Core\Form\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

final class DateRangeFilter
{
    public static function from(string $startProperty): \Closure
    {
        return static function (QueryInterface $filterQuery, string $field, array $values) use ($startProperty): null {
            $date = $values['value'] ?? null;
            if (!$date instanceof \DateTimeInterface) {
                return null;
            }

            $parameter = self::parameter($field);
            $filterQuery->getQueryBuilder()
                ->andWhere(sprintf('%s.%s >= :%s', self::alias($field), $startProperty, $parameter))
                ->setParameter($parameter, \DateTimeImmutable::createFromInterface($date)->setTime(0, 0));

            return null;
        };
    }

    public static function to(string $startProperty, string $endProperty): \Closure
    {
        return static function (QueryInterface $filterQuery, string $field, array $values) use ($startProperty, $endProperty): null {
            $date = $values['value'] ?? null;
            if (!$date instanceof \DateTimeInterface) {
                return null;
            }

            $parameter = self::parameter($field);
            $filterQuery->getQueryBuilder()
                ->andWhere(sprintf('COALESCE(%1$s.%2$s, %1$s.%3$s) < :%4$s', self::alias($field), $endProperty, $startProperty, $parameter))
                ->setParameter($parameter, \DateTimeImmutable::createFromInterface($date)->setTime(0, 0)->modify('+1 day'));

            return null;
        };
    }

    private static function alias(string $field): string
    {
        $dot = strrpos($field, '.');

        return $dot === false ? $field : substr($field, 0, $dot);
    }

    private static function parameter(string $field): string
    {
        return 'date_range_' . preg_replace('/\W/', '_', $field);
    }
}
