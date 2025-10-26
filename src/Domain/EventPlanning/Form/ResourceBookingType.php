<?php

namespace App\Domain\EventPlanning\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\Enum\BookingStatus;
use App\Domain\EventPlanning\Entity\PlanningResource;
use App\Domain\EventPlanning\Entity\ResourceBooking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResourceBookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('resource', EntityType::class, [
                'class' => PlanningResource::class,
                'label' => 'resource_booking.resource',
                'choice_label' => 'name',
                'placeholder' => 'resource_booking.resource_placeholder',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('pr')
                    ->where('pr.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('pr.name', 'ASC'),
                'autocomplete' => true,
            ])
            ->add('quantityNeeded', IntegerType::class, [
                'label' => 'resource_booking.quantity_needed',
                'attr' => ['min' => 1, 'max' => 999],
                'help' => 'resource_booking.quantity_needed_help',
            ])
            ->add('required', CheckboxType::class, [
                'label' => 'resource_booking.required',
                'required' => false,
                'help' => 'resource_booking.required_help',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'resource_booking.notes',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('status', EnumType::class, [
                'class' => BookingStatus::class,
                'label' => 'resource_booking.status',
                'choice_label' => fn (BookingStatus $status) => $status->value,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => ResourceBooking::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', [Larp::class]);
    }
}
