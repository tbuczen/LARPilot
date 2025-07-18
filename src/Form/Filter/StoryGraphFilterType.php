<?php

namespace App\Form\Filter;

use App\Entity\Larp;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Thread;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Repository\StoryObject\LarpFactionRepository;
use App\Repository\StoryObject\ThreadRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoryGraphFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
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
                'query_builder' => function (ThreadRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('involvedFactions', EntityType::class, [
                'class' => LarpFaction::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => function (LarpFactionRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('involvedCharacters', EntityType::class, [
                'class' => LarpCharacter::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => function (LarpCharacterRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ]);
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'larp_story_graph_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => array('filtering'),
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
