<?php

namespace App\Form;

use App\Entity\Enum\TargetType;
use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.tag.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.tag.description',
                'required' => false,
            ])
            ->add('target', ChoiceType::class, [
                'label' => 'form.tag.target',
                'choices' => TargetType::cases(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'placeholder' => 'form.choose',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
            'translation_domain' => 'forms',
        ]);
    }
}
