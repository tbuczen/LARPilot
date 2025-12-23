<?php

declare(strict_types=1);

namespace App\Domain\Survey\Form;

use App\Domain\Survey\Entity\Enum\SurveyQuestionType as SurveyQuestionTypeEnum;
use App\Domain\Survey\Entity\SurveyQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionText', TextareaType::class, [
                'label' => 'Question',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Enter your question here...',
                ],
            ])
            ->add('helpText', TextType::class, [
                'label' => 'Help Text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optional guidance for the applicant',
                ],
            ])
            ->add('questionType', EnumType::class, [
                'class' => SurveyQuestionTypeEnum::class,
                'choice_label' => fn (SurveyQuestionTypeEnum $type): string => $type->getLabel(),
                'label' => 'Question Type',
                'attr' => [
                    'class' => 'form-select',
                    'data-action' => 'change->survey-builder#onQuestionTypeChange',
                ],
            ])
            ->add('isRequired', CheckboxType::class, [
                'label' => 'Required',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('orderPosition', HiddenType::class, [
                'attr' => ['class' => 'question-order-position'],
            ])
            ->add('options', CollectionType::class, [
                'entry_type' => SurveyQuestionOptionType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Options',
                'attr' => [
                    'class' => 'question-options-container',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyQuestion::class,
            'translation_domain' => 'forms',
        ]);
    }
}
