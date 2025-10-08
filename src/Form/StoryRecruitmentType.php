<?php

namespace App\Form;

use App\Entity\StoryObject\StoryRecruitment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoryRecruitmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requiredNumber', IntegerType::class, [
                'label' => 'form.recruitment.number',
            ])
            ->add('type', TextType::class, [
                'label' => 'form.recruitment.type',
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'form.recruitment.notes',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoryRecruitment::class,
            'translation_domain' => 'forms',
        ]);
    }
}
