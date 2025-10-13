<?php

namespace App\Domain\EventPlanning\Form\Filter;

use App\Domain\EventPlanning\Entity\Enum\PlanningResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningResourceFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'filter.resource.name',
                'required' => false,
                'attr' => ['placeholder' => 'filter.resource.name_placeholder'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'filter.resource.type',
                'required' => false,
                'placeholder' => 'filter.all_types',
                'choices' => array_combine(
                    array_map(fn (PlanningResourceType $t) => $t->getLabel(), PlanningResourceType::cases()),
                    PlanningResourceType::cases()
                ),
            ])
            ->add('shareable', ChoiceType::class, [
                'label' => 'filter.resource.shareable',
                'required' => false,
                'placeholder' => 'filter.all',
                'choices' => [
                    'filter.shareable' => '1',
                    'filter.exclusive' => '0',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
        ]);
    }
}
