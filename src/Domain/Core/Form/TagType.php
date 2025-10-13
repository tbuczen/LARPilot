<?php

namespace App\Domain\Core\Form;

use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Enum\TargetType;
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
            ->add('title', TextType::class, [
                'label' => 'tag.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'tag.description',
                'required' => false,
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('target', ChoiceType::class, [
                'label' => 'tag.target',
                'choices' => TargetType::cases(),
                'choice_label' => fn (TargetType $type) => $type->name,
                'choice_value' => fn (?TargetType $type) => $type?->value,
                'placeholder' => 'choose',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
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
