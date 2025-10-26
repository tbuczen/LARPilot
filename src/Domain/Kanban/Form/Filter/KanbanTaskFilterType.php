<?php

namespace App\Domain\Kanban\Form\Filter;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Kanban\Entity\Enum\KanbanStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KanbanTaskFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp|null $larp */
        $larp = $options['larp'];

        $builder
            ->add('priority', ChoiceType::class, [
                'label' => 'filter.kanban_task.priority',
                'required' => false,
                'placeholder' => 'filter.all_priorities',
                'choices' => [
                    'filter.kanban_task.priority_high' => 'high',
                    'filter.kanban_task.priority_medium' => 'medium',
                    'filter.kanban_task.priority_low' => 'low',
                    'filter.kanban_task.priority_none' => '0',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'filter.kanban_task.status',
                'required' => false,
                'placeholder' => 'filter.all_statuses',
                'choice_label' => fn (KanbanStatus $status): string => 'kanban_task.status.' . strtolower($status->value),
                'choice_value' => fn (?KanbanStatus $choice) => $choice?->value,
                'choices' => KanbanStatus::cases(),
            ]);

        if ($larp) {
            $builder
                ->add('assignedTo', EntityType::class, [
                    'class' => LarpParticipant::class,
                    'label' => 'filter.kanban_task.assigned_to',
                    'required' => false,
                    'placeholder' => 'filter.all_users',
                    'choice_label' => fn (LarpParticipant $p) => $p->getUser()->getUsername(),
                    'query_builder' => fn ($repo) => $repo->createQueryBuilder('p')
                        ->innerJoin('p.user', 'u')
                        ->addSelect('u')
                        ->where('p.larp = :larp')
                        ->setParameter('larp', $larp)
                        ->orderBy('u.username', 'ASC'),
                    'autocomplete' => true,
                ]);
        }
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

        $resolver->setAllowedTypes('larp', ['null', Larp::class]);
    }
}
