<?php

namespace App\Domain\Core\Form\DataTransformer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class MoneyToFloatTransformer implements DataTransformerInterface
{
    public function transform($value): ?float
    {
        if ($value instanceof Money) {
            return $value->getAmount() / 100;
        }
        return $value;
    }

    public function reverseTransform($value): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }
        return new Money($value * 100, new Currency('USD'));
    }
}

//// Then in your buildForm method:
//->add('cost', MoneyType::class, [
//    'label' => 'item.cost',
//    'currency' => 'USD',
//])
//    ->get('cost')->addModelTransformer(new MoneyToFloatTransformer())
