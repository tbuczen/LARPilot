<?php

namespace App\Form;

use App\Entity\Larp;
use App\Entity\StoryObject\Event;
use App\Entity\StoryObject\LarpCharacter;
use App\Entity\StoryObject\LarpFaction;
use App\Entity\StoryObject\Place;
use App\Entity\Tag;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Repository\StoryObject\LarpFactionRepository;
use App\Repository\StoryObject\PlaceRepository;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.event.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.event.description',
            ])
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'title',
                'label' => 'form.event.place',
                'required' => false,
                'multiple' => false,
                'autocomplete' => true,
                'placeholder' => 'form.event.choose_place',
                'query_builder' => function (PlaceRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('p')
                        ->where('p.larp = :larp')
                        ->setParameter('larp', $larp);
                }
            ])
            ->add('startTime', DateTimeType::class, [
                'label' => 'form.event.start_time',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'form.event.end_time',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('involvedFactions', EntityType::class, [
                'class' => LarpFaction::class,
                'choice_label' => 'title',
                'label' => 'form.event.factions',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.event.choose_faction',
                'query_builder' => function (LarpFactionRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('involvedCharacters', EntityType::class, [
                'class' => LarpCharacter::class,
                'choice_label' => 'title',
                'label' => 'form.event.factions',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'placeholder' => 'form.event.choose_faction',
                'query_builder' => function (LarpCharacterRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('f')
                        ->where('f.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'label' => 'form.event.tags',
                'required' => false,
                'multiple' => true,
                'autocomplete' => true,
                'query_builder' => function (TagRepository $repo) use ($larp) {
                    return $repo->createQueryBuilder('t')
                        ->where('t.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
