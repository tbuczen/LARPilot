<?php

namespace App\Domain\StoryObject\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Repository\TagRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Entity\Thread;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\FactionRepository;
use App\Domain\StoryObject\Repository\ThreadRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('description', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => fn (TagRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default',
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => fn (ThreadRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
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
                    ->setParameter('larp', $larp)
                    ->orderBy('f.title', 'ASC'),
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
                'query_builder' => fn (CharacterRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('c.title', 'ASC'),
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'larp_quest_filter';
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
