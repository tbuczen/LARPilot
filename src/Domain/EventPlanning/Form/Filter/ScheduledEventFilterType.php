<?php

namespace App\Domain\EventPlanning\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\Enum\EventStatus;
use App\Domain\Map\Entity\MapLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduledEventFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp|null $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'filter.event.title',
                'required' => false,
                'attr' => ['placeholder' => 'filter.event.title_placeholder'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'filter.event.status',
                'required' => false,
                'placeholder' => 'filter.all_statuses',
                'choices' => array_combine(
                    array_map(fn (EventStatus $s) => $s->getLabel(), EventStatus::cases()),
                    EventStatus::cases()
                ),
            ])
            ->add('startDate', DateType::class, [
                'label' => 'filter.event.start_date',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'filter.event.end_date',
                'required' => false,
                'widget' => 'single_text',
            ]);

        if ($larp) {
            $builder->add('location', EntityType::class, [
                'class' => MapLocation::class,
                'label' => 'filter.event.location',
                'required' => false,
                'placeholder' => 'filter.all_locations',
                'choice_label' => 'name',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('ml')
                    ->join('ml.map', 'm')
                    ->where('m.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('ml.name', 'ASC'),
            ]);
        }
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
