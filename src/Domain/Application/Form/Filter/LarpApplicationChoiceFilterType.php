<?php

namespace App\Domain\Application\Form\Filter;

use App\Domain\Account\Entity\User;
use App\Domain\Account\Repository\UserRepository;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Faction;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\FactionRepository;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpApplicationChoiceFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('character', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'title',
                'required' => false,
                'autocomplete' => true,
                'placeholder' => 'choose',
                'label' => 'character_lbl',
                'data_extraction_method' => 'default',
                'query_builder' => fn (CharacterRepository $repo): QueryBuilder => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('c.title', 'ASC'),

            ])
            ->add('faction', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'required' => false,
                'autocomplete' => true,
                'placeholder' => 'choose',
                'label' => 'faction_lbl',
                'data_extraction_method' => 'default',
                'query_builder' => fn (FactionRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('f.title', 'ASC'),
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    // Access the QueryBuilder and apply custom filter through character's factions
                    $qb = $filterQuery->getQueryBuilder();
                    $qb
                        ->join('ch.factions', 'f')
                        ->addSelect('f')
                        ->andWhere('f = :filter_faction')
                        ->setParameter('filter_faction', $values['value']);
                    return null;
                },
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'required' => false,
                'autocomplete' => true,
                'placeholder' => 'choose',
                'label' => 'user',
                'data_extraction_method' => 'default',
                'query_builder' => fn (UserRepository $repo): QueryBuilder => $repo->createQueryBuilder('u')
                    ->innerJoin('u.applications', 'app')
                    ->where('app.larp = :larp')
                    ->setParameter('larp', $larp),
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    $qb->join('c.application', 'app')
                        ->andWhere('app.user = :filter_user')
                        ->setParameter('filter_user', $values['value']);
                    return null;
                },
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'larp_application_choice_filter';
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

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
