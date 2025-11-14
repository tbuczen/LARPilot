<?php

namespace App\Domain\Gallery\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Repository\LarpParticipantRepository;
use App\Domain\Gallery\Entity\Enum\GalleryVisibility;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GalleryFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp|null $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', Filters\TextFilterType::class, [
                'condition_pattern' => FilterOperands::STRING_CONTAINS,
                'label' => 'common.search',
                'required' => false,
            ])
            ->add('photographer', EntityType::class, [
                'class' => LarpParticipant::class,
                'choice_label' => fn (LarpParticipant $participant): string =>
                    $participant->getUser()->getUsername(),
                'required' => false,
                'placeholder' => 'common.all',
                'autocomplete' => true,
                'query_builder' => function (LarpParticipantRepository $repo) use ($larp) {
                    $qb = $repo->createQueryBuilder('p')
                        ->join('p.user', 'u');

                    if ($larp) {
                        $qb->where('p.larp = :larp')
                            ->setParameter('larp', $larp);
                    }

                    return $qb->orderBy('u.username', 'ASC');
                },
            ])
            ->add('visibility', Filters\EnumFilterType::class, [
                'class' => GalleryVisibility::class,
                'required' => false,
                'multiple' => false,
                'placeholder' => 'common.all',
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'gallery_filter';
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

        $resolver->setAllowedTypes('larp', ['null', Larp::class]);
    }
}
