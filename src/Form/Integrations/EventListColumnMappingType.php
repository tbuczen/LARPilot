<?php

namespace App\Form\Integrations;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventListColumnMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('eventName', TextType::class)
            ->add('description', TextType::class)
            ->add('factions', TextType::class)
        ;
    }
}
