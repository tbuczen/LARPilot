<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Form\Type;

use App\Domain\StoryObject\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'comment.content',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'comment.content_placeholder',
                ],
                'required' => true,
            ])
            ->add('isResolved', CheckboxType::class, [
                'label' => 'comment.is_resolved',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'translation_domain' => 'forms',
        ]);
    }
}
