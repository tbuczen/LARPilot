<?php

declare(strict_types=1);

namespace App\Domain\Core\Form;

use App\Domain\Core\Entity\Enum\LarpModule;
use App\Domain\Survey\Entity\Enum\ApplicationMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class LarpWizardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'larp.title',
                'attr' => ['class' => 'form-control', 'placeholder' => 'larp.title_placeholder'],
                'constraints' => [new NotBlank()],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'larp.start_date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'larp.end_date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('enableWipStage', CheckboxType::class, [
                'label' => 'wizard.enable_wip_stage',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('applicationMode', EnumType::class, [
                'class' => ApplicationMode::class,
                'label' => 'wizard.application_mode',
                'choice_label' => fn (ApplicationMode $mode) => $mode->getLabel(),
                'attr' => ['class' => 'form-select', 'data-larp-wizard-target' => 'applicationModeSelect'],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('maxCharacterChoices', IntegerType::class, [
                'label' => 'wizard.max_character_choices',
                'data' => 3,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 10],
                'constraints' => [new GreaterThanOrEqual(1)],
            ])
            ->add('applicationTurns', IntegerType::class, [
                'label' => 'wizard.application_turns',
                'data' => 1,
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [new GreaterThanOrEqual(1)],
                'help' => 'wizard.application_turns_help',
            ])
            ->add('enableNegotiationStage', CheckboxType::class, [
                'label' => 'wizard.enable_negotiation_stage',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('enableCostumeCheckStage', CheckboxType::class, [
                'label' => 'wizard.enable_costume_check_stage',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('enabledModules', ChoiceType::class, [
                'label' => false,
                'choices' => array_combine(
                    array_map(fn (LarpModule $m) => $m->getLabel(), LarpModule::cases()),
                    array_map(fn (LarpModule $m) => $m->value, LarpModule::cases()),
                ),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'data' => [LarpModule::STORY->value],
                'label_attr' => ['class' => 'form-check-label'],
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'forms',
        ]);
    }
}
