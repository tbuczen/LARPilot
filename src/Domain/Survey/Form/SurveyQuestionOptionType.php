<?php

declare(strict_types=1);

namespace App\Domain\Survey\Form;

use App\Domain\Survey\Entity\SurveyQuestionOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyQuestionOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('optionText', TextType::class, [
                'label' => 'Option',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter option text...',
                ],
            ])
            ->add('orderPosition', HiddenType::class, [
                'attr' => ['class' => 'option-order-position'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyQuestionOption::class,
            'translation_domain' => 'forms',
        ]);
    }
}
