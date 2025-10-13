<?php

namespace App\Domain\Core\Form;

use App\Domain\Core\Entity\Enum\LarpCharacterSystem;
use App\Domain\Core\Entity\Enum\LarpSetting;
use App\Domain\Core\Entity\Enum\LarpType;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LarpPropertiesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'title',
                'placeholder' => 'Select a location',
                'required' => false,
                'autocomplete' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('maxCharacterChoices', IntegerType::class, [
                'label' => 'Max Character Choices',
                'required' => true,
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('setting', EnumType::class, [
                'class' => LarpSetting::class,
                'choice_label' => fn (LarpSetting $setting): string => $setting->getLabel(),
                'placeholder' => 'Select a setting',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('type', EnumType::class, [
                'class' => LarpType::class,
                'choice_label' => fn (LarpType $type): string => $type->getLabel(),
                'placeholder' => 'Select a type',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('characterSystem', EnumType::class, [
                'class' => LarpCharacterSystem::class,
                'choice_label' => fn (LarpCharacterSystem $system): string => $system->getLabel(),
                'placeholder' => 'Select a character system',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update LARP Properties',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Larp::class,
        ]);
    }
}
