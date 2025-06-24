<?php

namespace App\Form\Filter;

use App\Entity\Enum\LarpIncidentStatus;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpIncidentFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('caseId', Filters\TextFilterType::class)
            ->add('status', Filters\EnumFilterType::class, [
                'class' => LarpIncidentStatus::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'larp_incident_filter';
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
