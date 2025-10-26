<?php

namespace App\Domain\StoryMarketplace\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Event;
use App\Domain\StoryObject\Entity\Quest;
use App\Domain\StoryObject\Entity\Thread;
use Doctrine\ORM\EntityRepository;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactionFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
//            ->add('tags', EntityType::class, [
//                'class' => Tag::class,
//                'choice_label' => 'title',
//                'multiple' => true,
//                'required' => false,
//                'label' => 'filter_tags',
//                'autocomplete' => true,
//                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
//                    ->where('t.larp = :larp')
//                    ->setParameter('larp', $larp)
//                    ->orderBy('t.title', 'ASC'),
//            ])
            ->add('thread', EntityType::class, [
                'class' => Thread::class,
                'choice_label' => 'title',
                'required' => false,
                'label' => 'filter_thread',
                'autocomplete' => true,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('th')
                    ->where('th.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('th.title', 'ASC'),
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    $qb->join('f.threads', 'filter_thread')
                        ->andWhere('filter_thread = :filter_thread_value')
                        ->setParameter('filter_thread_value', $values['value']);
                    return null;
                },
            ])
            ->add('quest', EntityType::class, [
                'class' => Quest::class,
                'choice_label' => 'title',
                'required' => false,
                'label' => 'filter_quest',
                'autocomplete' => true,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('q')
                    ->where('q.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('q.title', 'ASC'),
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    $qb->join('f.quests', 'filter_quest')
                        ->andWhere('filter_quest = :filter_quest_value')
                        ->setParameter('filter_quest_value', $values['value']);
                    return null;
                },
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'title',
                'required' => false,
                'label' => 'filter_event',
                'autocomplete' => true,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where('e.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('e.title', 'ASC'),
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    $qb->join('App\Domain\StoryObject\Entity\Event', 'filter_event', 'WITH', 'filter_event.larp = :larp')
                        ->join('filter_event.involvedFactions', 'involved_faction')
                        ->andWhere('involved_faction = f')
                        ->andWhere('filter_event = :filter_event_value')
                        ->setParameter('filter_event_value', $values['value']);
                    return null;
                },
            ])
            ->add('character', EntityType::class, [
                'class' => Character::class,
                'choice_label' => 'title',
                'required' => false,
                'label' => 'filter_character',
                'autocomplete' => true,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('c.title', 'ASC'),
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (empty($values['value'])) {
                        return null;
                    }
                    $qb = $filterQuery->getQueryBuilder();
                    $qb->join('f.members', 'filter_member')
                        ->andWhere('filter_member = :filter_character_value')
                        ->setParameter('filter_character_value', $values['value']);
                    return null;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET',
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', Larp::class);
    }
}
