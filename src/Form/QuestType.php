<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Faction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Thread;
use App\Entity\Tag;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\FactionRepository;
use App\Repository\StoryObject\ThreadRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.quest.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.quest.description',
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'label' => 'form.quest.thread',
                'required' => false,
                'multiple' => false,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
                'query_builder' => fn (ThreadRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
            ])
            ->add('involvedFactions', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'label' => 'form.quest.factions',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
                'query_builder' => fn (FactionRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
            ])
            ->add('involvedCharacters', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'title',
                'label' => 'form.quest.characters',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.choose',
                'query_builder' => fn (CharacterRepository $repo): QueryBuilder => $repo->createQueryBuilder('f')
                    ->where('f.larp = :larp')
                    ->setParameter('larp', $larp),
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'title',
                'label' => 'form.quest.tags',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => fn (TagRepository $repo): QueryBuilder => $repo->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp),
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quest::class,
            'translation_domain' => 'forms',
        ]);
        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
