<?php

namespace App\Domain\StoryObject\Form\Type;

use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\StoryObjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KnowledgeDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'knowledge_document.title',
                'attr' => [
                    'placeholder' => 'knowledge_document.title_placeholder',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'knowledge_document.category.title',
                'required' => false,
                'placeholder' => 'choose',
                'choices' => [
                    'knowledge_document.category.lore' => 'lore',
                    'knowledge_document.category.history' => 'history',
                    'knowledge_document.category.rules' => 'rules',
                    'knowledge_document.category.faction' => 'faction',
                    'knowledge_document.category.world' => 'world',
                    'knowledge_document.category.other' => 'other',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'knowledge_document.content',
                'required' => false,
                'attr' => [
                    'data-controller' => 'wysiwyg',
                    'rows' => 15,
                ],
            ])
            ->add('owners', EntityType::class, [
                'class' => StoryObject::class,
                'choice_label' => 'title',
                'label' => 'knowledge_document.owners',
                'required' => false,
                'multiple' => true,
                'query_builder' => fn (StoryObjectRepository $repo) => $repo->createQueryBuilder('so')
                    ->where('so.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('so.title', 'ASC'),
                'autocomplete' => true,
                'help' => 'knowledge_document.owners_help',
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'knowledge_document.is_public',
                'required' => false,
                'help' => 'knowledge_document.is_public_help',
            ])
            ->add('visibleTo', EntityType::class, [
                'class' => StoryObject::class,
                'choice_label' => 'title',
                'label' => 'knowledge_document.visible_to',
                'required' => false,
                'multiple' => true,
                'query_builder' => fn (StoryObjectRepository $repo) => $repo->createQueryBuilder('so')
                    ->where('so.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('so.title', 'ASC'),
                'autocomplete' => true,
                'help' => 'knowledge_document.visible_to_help',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'submit',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KnowledgeDocument::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
