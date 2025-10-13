<?php

namespace App\Domain\StoryMarketplace\Form;

use App\Domain\StoryMarketplace\Entity\StoryRecruitment;
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
                'label' => 'recruitment.number',
            ])
            ->add('type', TextType::class, [
                'label' => 'recruitment.type',
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'recruitment.notes',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
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
