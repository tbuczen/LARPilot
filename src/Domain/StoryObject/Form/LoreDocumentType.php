<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Enum\LoreDocumentCategory;
use App\Domain\StoryObject\Entity\LoreDocument;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoreDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'lore.title',
            ])
            ->add('category', EnumType::class, [
                'class' => LoreDocumentCategory::class,
                'label' => 'lore.category',
                'choice_label' => fn (LoreDocumentCategory $category) => $category->getLabel(),
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'lore.summary',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'lore.summary_placeholder',
                ],
                'help' => 'lore.summary_help',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'lore.content',
                'required' => false,
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'lore.priority',
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                ],
                'help' => 'lore.priority_help',
            ])
            ->add('alwaysIncludeInContext', CheckboxType::class, [
                'label' => 'lore.always_include',
                'required' => false,
                'help' => 'lore.always_include_help',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'lore.active',
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'query_builder' => fn (EntityRepository $repo) => $repo->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
                'choice_label' => 'title',
                'multiple' => true,
                'required' => false,
                'label' => 'tags',
                'autocomplete' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LoreDocument::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
