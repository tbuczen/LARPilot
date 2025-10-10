<?php

namespace App\Form\Filter;

use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Faction;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\FactionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpApplicationFilterType extends AbstractType
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
                'placeholder' => 'form.choose',
                'data_extraction_method' => 'default',
                'query_builder' => fn (CharacterRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp),
            ])
            ->add('faction', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'required' => false,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
                'data_extraction_method' => 'default',
                'query_builder' => fn (FactionRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'larp_application_filter';
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
