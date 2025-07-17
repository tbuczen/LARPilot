<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Money\Money;
use Money\Currency;

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
//    'label' => 'form.item.cost',
//    'currency' => 'USD',
//])
//    ->get('cost')->addModelTransformer(new MoneyToFloatTransformer())