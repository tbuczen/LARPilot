<?php

namespace App\Domain\EventPlanning\Form;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\EventPlanning\Entity\Enum\PlanningResourceType as PlanningResourceTypeEnum;
use App\Domain\EventPlanning\Entity\PlanningResource;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Item;
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

class PlanningResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Larp $larp */
        $larp = $options['larp'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'planning_resource.name',
                'attr' => ['placeholder' => 'planning_resource.name_placeholder'],
            ])
            ->add('type', EnumType::class, [
                'class' => PlanningResourceTypeEnum::class,
                'label' => 'planning_resource.type',
                'choice_label' => fn (PlanningResourceTypeEnum $type) => $type->getLabel(),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'planning_resource.description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'planning_resource.quantity',
                'attr' => ['min' => 1],
            ])
            ->add('shareable', CheckboxType::class, [
                'label' => 'planning_resource.shareable',
                'required' => false,
                'help' => 'planning_resource.shareable_help',
            ])
            ->add('availableFrom', DateTimeType::class, [
                'label' => 'planning_resource.available_from',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('availableUntil', DateTimeType::class, [
                'label' => 'planning_resource.available_until',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('character', EntityType::class, [
                'class' => Character::class,
                'label' => 'planning_resource.character',
                'required' => false,
                'placeholder' => 'planning_resource.character_placeholder',
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('c')
                    ->where('c.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('c.title', 'ASC'),
                'autocomplete' => true,
            ])
            ->add('item', EntityType::class, [
                'class' => Item::class,
                'label' => 'planning_resource.item',
                'required' => false,
                'placeholder' => 'planning_resource.item_placeholder',
                'choice_label' => 'title',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('i')
                    ->where('i.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('i.title', 'ASC'),
                'autocomplete' => true,
            ])
            ->add('participant', EntityType::class, [
                'class' => LarpParticipant::class,
                'label' => 'planning_resource.participant',
                'required' => false,
                'placeholder' => 'planning_resource.participant_placeholder',
                'choice_label' => fn (LarpParticipant $p) => $p->getUser()->getFullName(),
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('p')
                    ->join('p.user', 'u')
                    ->where('p.larp = :larp')
                    ->setParameter('larp', $larp)
                    ->orderBy('u.firstName', 'ASC'),
                'autocomplete' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PlanningResource::class,
            'translation_domain' => 'forms',
            'larp' => null,
        ]);

        $resolver->setRequired('larp');
        $resolver->setAllowedTypes('larp', [Larp::class]);
    }
}
