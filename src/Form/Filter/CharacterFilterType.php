<?php

namespace App\Form\Filter;

use App\Entity\Enum\CharacterType;
use App\Entity\Enum\Gender;
use App\Entity\Enum\UserRole;
use App\Entity\Larp;
use App\Entity\LarpParticipant;
use App\Entity\StoryObject\Faction;
use App\Entity\Tag;
use App\Repository\LarpParticipantRepository;
use App\Repository\StoryObject\FactionRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('inGameName', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
            ])
            ->add('gender', Filters\EnumFilterType::class, [
                'class' => Gender::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
            ])
            ->add('characterType', Filters\EnumFilterType::class, [
                'class' => CharacterType::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
            ])
            ->add('factions', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default', // potrzebne przez FilterBundle
                'tom_select_options' => [
                    'hideSelected' => false
                ],
                'query_builder' => fn (FactionRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
            ->add('storyWriter', EntityType::class, [
                'class' => LarpParticipant::class,
                'choice_label' => 'user.username',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default', // potrzebne przez FilterBundle
                'query_builder' => function (LarpParticipantRepository $repo) use ($larp): \Doctrine\ORM\QueryBuilder {
                    $qb = $repo->createQueryBuilder('p')
                        ->join('p.user', 'u')
                        ->andWhere('p.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('u.username', 'ASC');

                    $roles = UserRole::getStoryWriters();
                    $orX = $qb->expr()->orX();

                    foreach ($roles as $i => $role) {
                        $orX->add("JSONB_EXISTS(p.roles, :role_$i) = true");
                        $qb->setParameter("role_$i", $role);
                    }
                    $qb->andWhere($orX);
                    return $qb;
                },
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'data_extraction_method' => 'default', // potrzebne przez FilterBundle
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'larp_character_filter';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
