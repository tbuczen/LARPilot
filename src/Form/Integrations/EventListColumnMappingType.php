<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventListColumnMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sheetName', TextType::class)
            ->add('endColumn', TextType::class)
            ->add('startingRow', IntegerType::class, [
                'data' => 2,
            ])
            ->add('eventName', TextType::class)
            ->add('description', TextType::class)
            ->add('faction', TextType::class)
            ;
    }
}
