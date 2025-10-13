<?php

namespace App\Domain\EventPlanning\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\EventPlanning\Entity\Enum\EventStatus;
use App\Domain\EventPlanning\Entity\ScheduledEvent;
use App\Domain\Map\Entity\MapLocation;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScheduledEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'scheduled_event.title',
                'attr' => ['placeholder' => 'scheduled_event.title_placeholder'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'scheduled_event.description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('startTime', DateTimeType::class, [
                'label' => 'scheduled_event.start_time',
                'widget' => 'single_text',
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'scheduled_event.end_time',
                'widget' => 'single_text',
            ])
            ->add('setupMinutes', IntegerType::class, [
                'label' => 'scheduled_event.setup_minutes',
                'required' => false,
                'attr' => ['min' => 0],
                'help' => 'scheduled_event.setup_minutes_help',
            ])
            ->add('cleanupMinutes', IntegerType::class, [
                'label' => 'scheduled_event.cleanup_minutes',
                'required' => false,
                'attr' => ['min' => 0],
                'help' => 'scheduled_event.cleanup_minutes_help',
            ])
            ->add('status', EnumType::class, [
                'class' => EventStatus::class,
                'label' => 'scheduled_event.status',
                'choice_label' => fn (EventStatus $status) => $status->getLabel(),
            ])
            ->add('visibleToPlayers', CheckboxType::class, [
                'label' => 'scheduled_event.visible_to_players',
                'required' => false,
            ])
            ->add('organizerNotes', TextareaType::class, [
                'label' => 'scheduled_event.organizer_notes',
                'required' => false,
                'attr' => ['rows' => 3],
                'help' => 'scheduled_event.organizer_notes_help',
            ])
            ->add('location', EntityType::class, [
                'class' => MapLocation::class,
                'label' => 'scheduled_event.location',
                'required' => false,
                'placeholder' => 'scheduled_event.location_placeholder',
                'choice_label' => 'name',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('ml')
                    ->join('ml.map', 'm')
                    ->where('m.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('ml.name', 'ASC'),
                'autocomplete' => true,
            ])
            ->add('quest', EntityType::class, [
                'class' => Quest::class,
                'label' => 'scheduled_event.quest',
                'required' => false,
                'placeholder' => 'scheduled_event.quest_placeholder',
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('q')
                    ->where('q.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('q.title', 'ASC'),
                'autocomplete' => true,
            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'label' => 'scheduled_event.thread',
                'required' => false,
                'placeholder' => 'scheduled_event.thread_placeholder',
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('t')
                    ->where('t.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('t.title', 'ASC'),
                'autocomplete' => true,
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'label' => 'scheduled_event.event',
                'required' => false,
                'placeholder' => 'scheduled_event.event_placeholder',
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('e')
                    ->where('e.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('e.title', 'ASC'),
                'autocomplete' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ScheduledEvent::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', [Larp::class]);
    }
}
