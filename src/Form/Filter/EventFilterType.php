<?php

namespace App\Form\Filter;

use App\Entity\Larp;
use App\Entity\StoryObject\Thread;
use App\Repository\StoryObject\ThreadRepository;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('startTime', Filters\DateTimeFilterType::class, [
                'widget' => 'single_text',
            ])
            ->add('endTime', Filters\DateTimeFilterType::class, [
                'widget' => 'single_text',
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
//                    'plugins' =>  ['dropdown_input']
                'hideSelected' => false
                ],
                'query_builder' => fn (ThreadRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'larp_event_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
