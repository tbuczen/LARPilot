<?php

namespace App\Form\Filter;

use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Faction;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\FactionRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThreadFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('involvedFactions', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => fn (FactionRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
            ->add('involvedCharacters', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => fn (CharacterRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
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
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
