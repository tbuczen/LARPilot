<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\StoryObject\Character;
use App\Entity\StoryObject\Faction;
use App\Entity\StoryObject\Thread;
use App\Entity\Tag;
use App\Repository\StoryObject\CharacterRepository;
use App\Repository\StoryObject\FactionRepository;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.thread.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.thread.description',
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('involvedFactions', EntityType::class, [
                'class' => Faction::class,
                'choice_label' => 'title',
                'label' => 'form.thread.factions',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.thread.choose_faction',
                'query_builder' => fn (FactionRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('f')
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
                'label' => 'form.thread.characters',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.thread.choose_faction',
                'query_builder' => fn (CharacterRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('f')
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
                'label' => 'form.thread.tags',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => fn (TagRepository $repo): \Doctrine\ORM\QueryBuilder => $repo->createQueryBuilder('t')
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
            'data_class' => Thread::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
