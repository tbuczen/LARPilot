<?php

namespace App\Form;

use App\Entity\Enum\TargetType;
use App\Entity\Larp;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Quest;
use App\Entity\StoryObject\Thread;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Repository\StoryObject\LarpFactionRepository;
use App\Repository\StoryObject\ThreadRepository;
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
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'label' => 'form.quest.thread',
                'required' => false,
                'multiple' => false,
                'autocomplete' => true,
                'placeholder' => 'form.quest.choose_thread',
                'query_builder' => function (ThreadRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
            ])
            ->add('involvedFactions', EntityType::class, [
                'class' => LarpFaction::class,
                'choice_label' => 'title',
                'label' => 'form.quest.factions',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.quest.choose_faction',
                'query_builder' => function (LarpFactionRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
                'tom_select_options' => [
                    'create' => true,
                    'persist' => false,
                ],
            ])
            ->add('involvedCharacters', EntityType::class, [
                'class' => LarpCharacter::class,
                'choice_label' => 'title',
                'label' => 'form.quest.characters',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.quest.choose_character',
                'query_builder' => function (LarpCharacterRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
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