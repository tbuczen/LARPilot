<?php

namespace App\Form;

use App\Entity\KanbanTask;
use App\Entity\LarpParticipant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KanbanTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $larp = $options['larp'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.kanban.title',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.kanban.description',
                'required' => false,
                'attr' => [
                    'data-controller' => 'wysiwyg',
                ],
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => LarpParticipant::class,
                'choice_label' => 'name',
                'label' => 'form.kanban.assigned_to',
                'required' => false,
                'autocomplete' => true,
                'placeholder' => 'form.kanban.unassigned',
                'query_builder' => function ($repo) use ($larp) {
                    return $repo->createQueryBuilder('lp')
                        ->where('lp.larp = :larp')
                        ->setParameter('larp', $larp);
                },
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'form.kanban.priority',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 10,
                ],
                'data' => 0,
            ])
            ->add('dueDate', DateTimeType::class, [
                'label' => 'form.kanban.due_date',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
            ])
        ;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KanbanTask::class,
            'translation_domain' => 'forms',
        ]);
        $resolver->setRequired(['larp']);

    }
}
