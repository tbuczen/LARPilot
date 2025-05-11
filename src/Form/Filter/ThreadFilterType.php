<?php

namespace App\Form\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThreadFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
//            ->add('thread', EntityType::class, [
//                'class' => Thread::class,
//                'choice_label' => 'title',
//                'multiple' => true,
//                'required' => false,
//                'autocomplete' => true,
//                'data_extraction_method' => 'default',
//                'tom_select_options' => [
//                'hideSelected' => false
//                ]
//            ])
            ;
    }

    public function getBlockPrefix(): string
    {
        return 'larp_thread_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => array('filtering'),
            'method' => 'GET',
            'translation_domain' => 'forms',
        ]);
    }
}