<?php

declare(strict_types=1);

namespace App\Domain\Public\Form\Filter;

use App\Domain\Core\Entity\Enum\Gender;
use App\Domain\Core\Entity\Tag;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterGalleryFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $larp = $options['larp'];

        $builder
            ->add('search', TextType::class, [
                'label' => 'Search',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Search by name or description...',
                    'class' => 'form-control',
                ],
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $qb = $filterQuery->getQueryBuilder();
                    $qb->andWhere('(c.title LIKE :search OR c.description LIKE :search)')
                        ->setParameter('search', '%' . $values['value'] . '%');

                    return null;
                },
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'label' => 'Tags',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
                'attr' => ['class' => 'form-select'],
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }

                    $qb = $filterQuery->getQueryBuilder();
                    $qb->join('c.tags', 't')
                        ->andWhere('t IN (:tags)')
                        ->setParameter('tags', $values['value']);

                    return null;
                },
            ])
            ->add('gender', EnumType::class, [
                'class' => Gender::class,
                'label' => 'Gender',
                'required' => false,
                'placeholder' => 'Any gender',
                'choice_label' => fn (Gender $gender): string => $gender->name,
                'attr' => ['class' => 'form-select'],
            ])
        ;
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
    }
}
