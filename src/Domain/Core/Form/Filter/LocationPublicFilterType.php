<?php

namespace App\Domain\Core\Form\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationPublicFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', Filters\TextFilterType::class, [
                'required' => false,
                'label' => 'Search',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search by name...',
                ],
//                'condition_pattern' => Filters\TextFilterType::PATTERN_CONTAINS,
            ])
            ->add('city', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'All Cities',
                'attr' => ['class' => 'form-select'],
                'choices' => $options['cities'] ?? [],
            ])
            ->add('country', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'All Countries',
                'attr' => ['class' => 'form-select'],
                'choices' => $options['countries'] ?? [],
            ])
            ->add('minCapacity', Filters\NumberFilterType::class, [
                'required' => false,
                'label' => 'Min Capacity',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Min',
                ],
            ])
            ->add('maxCapacity', Filters\NumberFilterType::class, [
                'required' => false,
                'label' => 'Max Capacity',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Max',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => false,
            'method' => 'GET',
            'translation_domain' => 'forms',
            'cities' => [],
            'countries' => [],
        ]);
    }
}
