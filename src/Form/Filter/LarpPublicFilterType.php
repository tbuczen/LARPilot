<?php

namespace App\Form\Filter;

use App\Entity\Enum\LarpCharacterSystem;
use App\Entity\Enum\LarpSetting;
use App\Entity\Enum\LarpStageStatus;
use App\Entity\Enum\LarpType;
use App\Entity\Enum\TargetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class LarpPublicFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //TODO:: filtering start date and end date should be >= start date and <= endDate
        //statuses, settings, types, must be multiple OR
        //location needs to be autocomplete to location entity
        //PRO: there should be option to localise a larp in range of given address
        //duration should be a range from available larps min and max durations
        $builder
            ->add('status', Filters\ChoiceFilterType::class, [
                'choices' => [
                    'Published' => LarpStageStatus::PUBLISHED,
                    'Open for Inquiries' => LarpStageStatus::INQUIRIES,
                    'Confirmed' => LarpStageStatus::CONFIRMED,
                    'Completed' => LarpStageStatus::COMPLETED,
                ],
                'required' => false,
                'placeholder' => 'All Statuses',
                'attr' => ['class' => 'form-select']
            ])
            ->add('setting', Filters\EnumFilterType::class, [
                'class' => LarpSetting::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
            ])
            ->add('type', Filters\EnumFilterType::class, [
                'class' => LarpType::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
            ])
            ->add('characterSystem', Filters\EnumFilterType::class, [
                'class' => LarpCharacterSystem::class,
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
            ])
            ->add('location', Filters\TextFilterType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search by location...'
                ]
            ])
            ->add('startDate', Filters\DateFilterType::class, [
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', Filters\DateFilterType::class, [
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('minDuration', Filters\NumberFilterType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Min days'
                ]
            ])
            ->add('maxDuration', Filters\NumberFilterType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Max days'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => false,
            'method' => 'GET',
        ]);
    }
}
